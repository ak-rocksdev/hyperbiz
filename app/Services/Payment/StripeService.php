<?php

namespace App\Services\Payment;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeService
{
    protected SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
        Stripe::setApiKey(config('stripe.secret'));
    }

    /**
     * Create a Stripe Checkout session for subscription payment.
     */
    public function createCheckoutSession(Invoice $invoice): Session
    {
        $company = $invoice->company;
        $plan = $invoice->subscriptionPlan;

        // Convert IDR to cents (Stripe uses smallest currency unit)
        $amountInCents = (int) ($invoice->amount * 100);

        $session = Session::create([
            'payment_method_types' => config('stripe.checkout.payment_method_types', ['card']),
            'mode' => 'payment',
            'customer_email' => $company->email,
            'client_reference_id' => $invoice->id,
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => strtolower($invoice->currency),
                        'product_data' => [
                            'name' => $plan->name . ' Subscription',
                            'description' => $plan->description ?? "Subscription plan for {$invoice->billing_cycle} billing",
                        ],
                        'unit_amount' => $amountInCents,
                    ],
                    'quantity' => 1,
                ],
            ],
            'metadata' => [
                'invoice_id' => $invoice->id,
                'company_id' => $company->id,
                'plan_id' => $plan->id,
                'billing_cycle' => $invoice->billing_cycle,
            ],
            'success_url' => url(config('stripe.checkout.success_url')) . '?session_id={CHECKOUT_SESSION_ID}&invoice_id=' . $invoice->id,
            'cancel_url' => url(config('stripe.checkout.cancel_url')) . '?invoice_id=' . $invoice->id,
            'expires_at' => now()->addMinutes(config('stripe.checkout.expires_after_minutes', 30))->timestamp,
        ]);

        // Update payment transaction with Stripe session ID
        $invoice->paymentTransactions()->update([
            'transaction_id' => $session->id,
            'gateway_response' => [
                'session_id' => $session->id,
                'session_url' => $session->url,
            ],
        ]);

        Log::info("Stripe checkout session created", [
            'session_id' => $session->id,
            'invoice_id' => $invoice->id,
            'company_id' => $company->id,
        ]);

        return $session;
    }

    /**
     * Handle Stripe webhook event.
     */
    public function handleWebhook(string $payload, string $signature): array
    {
        try {
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                config('stripe.webhook_secret')
            );
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => 'Invalid signature'];
        }

        Log::info("Stripe webhook received", [
            'type' => $event->type,
            'id' => $event->id,
        ]);

        switch ($event->type) {
            case 'checkout.session.completed':
                return $this->handleCheckoutSessionCompleted($event->data->object);

            case 'payment_intent.succeeded':
                return $this->handlePaymentIntentSucceeded($event->data->object);

            case 'payment_intent.payment_failed':
                return $this->handlePaymentIntentFailed($event->data->object);

            default:
                Log::info("Unhandled Stripe webhook event", ['type' => $event->type]);
                return ['success' => true, 'message' => 'Event ignored'];
        }
    }

    /**
     * Handle checkout session completed event.
     */
    protected function handleCheckoutSessionCompleted($session): array
    {
        $invoiceId = $session->metadata->invoice_id ?? $session->client_reference_id ?? null;

        if (!$invoiceId) {
            Log::error('Stripe checkout session completed but no invoice ID found', [
                'session_id' => $session->id,
            ]);
            return ['success' => false, 'message' => 'Invoice not found'];
        }

        $invoice = Invoice::find($invoiceId);

        if (!$invoice) {
            Log::error('Invoice not found for Stripe checkout', [
                'invoice_id' => $invoiceId,
                'session_id' => $session->id,
            ]);
            return ['success' => false, 'message' => 'Invoice not found'];
        }

        // Payment was successful
        if ($session->payment_status === 'paid') {
            // Update transaction
            $transaction = $invoice->paymentTransactions()->latest()->first();
            if ($transaction) {
                $transaction->update([
                    'status' => PaymentTransaction::STATUS_SUCCESS,
                    'transaction_id' => $session->payment_intent ?? $session->id,
                    'gateway_response' => [
                        'session_id' => $session->id,
                        'payment_intent' => $session->payment_intent,
                        'payment_status' => $session->payment_status,
                        'amount_total' => $session->amount_total,
                        'currency' => $session->currency,
                        'customer_email' => $session->customer_email,
                    ],
                ]);
            }

            // Activate subscription
            $this->subscriptionService->activateSubscription(
                $invoice->company,
                $invoice->subscriptionPlan,
                $invoice->billing_cycle,
                $invoice
            );

            Log::info("Stripe payment completed, subscription activated", [
                'invoice_id' => $invoice->id,
                'company_id' => $invoice->company_id,
                'plan_id' => $invoice->subscription_plan_id,
            ]);

            return ['success' => true, 'message' => 'Payment processed successfully'];
        }

        return ['success' => false, 'message' => 'Payment not completed'];
    }

    /**
     * Handle payment intent succeeded event.
     */
    protected function handlePaymentIntentSucceeded($paymentIntent): array
    {
        Log::info("Payment intent succeeded", [
            'payment_intent_id' => $paymentIntent->id,
        ]);

        return ['success' => true, 'message' => 'Payment intent succeeded'];
    }

    /**
     * Handle payment intent failed event.
     */
    protected function handlePaymentIntentFailed($paymentIntent): array
    {
        Log::warning("Payment intent failed", [
            'payment_intent_id' => $paymentIntent->id,
            'error' => $paymentIntent->last_payment_error?->message,
        ]);

        // Find and update the transaction
        $transaction = PaymentTransaction::where('transaction_id', $paymentIntent->id)->first();

        if ($transaction) {
            $transaction->update([
                'status' => PaymentTransaction::STATUS_FAILED,
                'failure_reason' => $paymentIntent->last_payment_error?->message ?? 'Payment failed',
                'gateway_response' => [
                    'payment_intent_id' => $paymentIntent->id,
                    'error' => $paymentIntent->last_payment_error,
                ],
            ]);

            // Update invoice status
            $transaction->invoice->update([
                'status' => Invoice::STATUS_FAILED,
            ]);
        }

        return ['success' => true, 'message' => 'Payment failure recorded'];
    }

    /**
     * Retrieve a checkout session.
     */
    public function retrieveCheckoutSession(string $sessionId): ?Session
    {
        try {
            return Session::retrieve($sessionId);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve Stripe checkout session', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Verify payment was successful.
     */
    public function verifyPayment(string $sessionId, int $invoiceId): bool
    {
        $session = $this->retrieveCheckoutSession($sessionId);

        if (!$session) {
            return false;
        }

        // Verify the session matches the invoice
        $metadataInvoiceId = $session->metadata->invoice_id ?? $session->client_reference_id ?? null;

        if ((int) $metadataInvoiceId !== $invoiceId) {
            Log::warning('Stripe session invoice mismatch', [
                'session_invoice_id' => $metadataInvoiceId,
                'expected_invoice_id' => $invoiceId,
            ]);
            return false;
        }

        return $session->payment_status === 'paid';
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\PaymentProof;
use App\Models\PaymentTransaction;
use App\Models\SubscriptionPlan;
use App\Services\InvoiceService;
use App\Services\SubscriptionService;
use App\Services\Payment\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    protected SubscriptionService $subscriptionService;
    protected InvoiceService $invoiceService;

    public function __construct(
        SubscriptionService $subscriptionService,
        InvoiceService $invoiceService
    ) {
        $this->subscriptionService = $subscriptionService;
        $this->invoiceService = $invoiceService;
    }

    /**
     * Display subscription dashboard.
     */
    public function index()
    {
        $company = auth()->user()->company;
        $currentPlan = $company->subscriptionPlan;
        $usage = $this->subscriptionService->getUsageStats($company);
        $limits = $this->subscriptionService->checkLimits($company);

        // Get pending invoices
        $pendingInvoices = $this->invoiceService->getPendingInvoices($company);

        // Get recent invoices
        $recentInvoices = Invoice::forCompany($company->id)
            ->with(['subscriptionPlan'])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'plan_name' => $invoice->subscriptionPlan?->name ?? 'N/A',
                'amount' => $invoice->amount,
                'formatted_amount' => $invoice->formatted_amount,
                'status' => $invoice->status,
                'status_label' => $invoice->status_label,
                'status_badge' => $invoice->status_badge,
                'billing_cycle' => $invoice->billing_cycle,
                'due_date' => $invoice->due_date->format('d M Y'),
                'paid_at' => $invoice->paid_at?->format('d M Y'),
                'created_at' => $invoice->created_at->format('d M Y'),
            ]);

        return Inertia::render('Subscription/Index', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'subscription_status' => $company->subscription_status,
                'subscription_status_label' => $company->subscription_status_label,
                'trial_ends_at' => $company->trial_ends_at?->format('d M Y'),
                'trial_days_remaining' => $company->trialDaysRemaining(),
                'subscription_starts_at' => $company->subscription_starts_at?->format('d M Y'),
                'subscription_ends_at' => $company->subscription_ends_at?->format('d M Y'),
                'billing_cycle' => $company->billing_cycle,
                'is_on_trial' => $company->isOnTrial(),
                'is_active' => $company->isSubscriptionActive(),
                'is_expired' => $company->isExpired(),
            ],
            'currentPlan' => $currentPlan ? [
                'id' => $currentPlan->id,
                'name' => $currentPlan->name,
                'description' => $currentPlan->description,
                'price_monthly' => $currentPlan->price_monthly,
                'price_yearly' => $currentPlan->price_yearly,
                'formatted_price_monthly' => 'Rp ' . number_format($currentPlan->price_monthly, 0, ',', '.'),
                'formatted_price_yearly' => 'Rp ' . number_format($currentPlan->price_yearly, 0, ',', '.'),
                'max_users' => $currentPlan->max_users,
                'max_products' => $currentPlan->max_products,
                'max_customers' => $currentPlan->max_customers,
                'max_monthly_orders' => $currentPlan->max_monthly_orders,
                'features' => $currentPlan->features,
            ] : null,
            'usage' => $usage,
            'limits' => $limits,
            'pendingInvoices' => $pendingInvoices->map(fn($invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'plan_name' => $invoice->subscriptionPlan?->name ?? 'N/A',
                'amount' => $invoice->amount,
                'formatted_amount' => $invoice->formatted_amount,
                'status' => $invoice->status,
                'due_date' => $invoice->due_date->format('d M Y'),
            ]),
            'recentInvoices' => $recentInvoices,
        ]);
    }

    /**
     * Display available plans.
     */
    public function showPlans()
    {
        $company = auth()->user()->company;
        $currentPlan = $company->subscriptionPlan;

        $plans = SubscriptionPlan::active()
            ->ordered()
            ->get()
            ->map(fn($plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'description' => $plan->description,
                'price_monthly' => $plan->price_monthly,
                'price_yearly' => $plan->price_yearly,
                'formatted_price_monthly' => 'Rp ' . number_format($plan->price_monthly, 0, ',', '.'),
                'formatted_price_yearly' => 'Rp ' . number_format($plan->price_yearly, 0, ',', '.'),
                'yearly_discount' => $plan->yearly_discount_percentage,
                'max_users' => $plan->max_users,
                'max_products' => $plan->max_products,
                'max_customers' => $plan->max_customers,
                'max_monthly_orders' => $plan->max_monthly_orders,
                'features' => $plan->features,
                'is_current' => $currentPlan && $currentPlan->id === $plan->id,
                'is_popular' => $plan->slug === 'professional', // Mark professional as popular
            ]);

        return Inertia::render('Subscription/Plans', [
            'plans' => $plans,
            'currentPlan' => $currentPlan ? [
                'id' => $currentPlan->id,
                'name' => $currentPlan->name,
            ] : null,
            'company' => [
                'subscription_status' => $company->subscription_status,
                'is_on_trial' => $company->isOnTrial(),
            ],
            'paymentMethods' => [
                'stripe' => config('subscription.payment_methods.stripe'),
                'bank_transfer' => config('subscription.payment_methods.bank_transfer'),
            ],
        ]);
    }

    /**
     * Show checkout page for a plan.
     */
    public function checkout(SubscriptionPlan $plan, Request $request)
    {
        $company = auth()->user()->company;
        $billingCycle = $request->input('cycle', 'monthly');

        $amount = $billingCycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;

        return Inertia::render('Subscription/Checkout', [
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->name,
                'description' => $plan->description,
                'price_monthly' => $plan->price_monthly,
                'price_yearly' => $plan->price_yearly,
                'formatted_price_monthly' => 'Rp ' . number_format($plan->price_monthly, 0, ',', '.'),
                'formatted_price_yearly' => 'Rp ' . number_format($plan->price_yearly, 0, ',', '.'),
                'features' => $plan->features,
            ],
            'billingCycle' => $billingCycle,
            'amount' => $amount,
            'formattedAmount' => 'Rp ' . number_format($amount, 0, ',', '.'),
            'company' => [
                'name' => $company->name,
                'email' => $company->email,
            ],
            'bankAccounts' => config('subscription.bank_accounts'),
            'paymentMethods' => [
                'stripe' => config('subscription.payment_methods.stripe'),
                'bank_transfer' => config('subscription.payment_methods.bank_transfer'),
            ],
        ]);
    }

    /**
     * Subscribe to a plan (create invoice).
     */
    public function subscribe(SubscriptionPlan $plan, Request $request)
    {
        $validated = $request->validate([
            'billing_cycle' => 'required|in:monthly,yearly',
            'payment_method' => 'required|in:stripe,bank_transfer',
        ]);

        $company = auth()->user()->company;

        // Create invoice
        $invoice = $this->subscriptionService->createSubscriptionInvoice(
            $company,
            $plan,
            $validated['billing_cycle'],
            $validated['payment_method']
        );

        if ($validated['payment_method'] === 'stripe') {
            // Redirect to Stripe checkout
            return response()->json([
                'success' => true,
                'redirect' => route('subscription.stripe.checkout', $invoice->id),
            ]);
        }

        // Bank transfer - redirect to payment proof upload
        return response()->json([
            'success' => true,
            'invoice_id' => $invoice->id,
            'redirect' => route('subscription.payment-proof', $invoice->id),
        ]);
    }

    /**
     * Show payment proof upload page.
     */
    public function showPaymentProof(Invoice $invoice)
    {
        $company = auth()->user()->company;

        // Ensure invoice belongs to company
        if ($invoice->company_id !== $company->id) {
            abort(403, 'Unauthorized');
        }

        // Get existing payment proofs
        $existingProofs = PaymentProof::whereHas('paymentTransaction', function ($q) use ($invoice) {
            $q->where('invoice_id', $invoice->id);
        })->latest()->get()->map(fn($proof) => [
            'id' => $proof->id,
            'file_name' => $proof->file_name,
            'file_url' => $proof->file_url,
            'file_type' => $proof->file_type,
            'status' => $proof->status,
            'status_label' => $proof->status_label,
            'status_badge' => $proof->status_badge,
            'rejection_reason' => $proof->rejection_reason,
            'transfer_date' => $proof->transfer_date->format('d M Y'),
            'formatted_transfer_amount' => $proof->formatted_transfer_amount,
            'created_at' => $proof->created_at->format('d M Y H:i'),
        ]);

        return Inertia::render('Subscription/PaymentProof', [
            'invoice' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'amount' => $invoice->amount,
                'formatted_amount' => $invoice->formatted_amount,
                'status' => $invoice->status,
                'status_label' => $invoice->status_label,
                'due_date' => $invoice->due_date->format('d M Y'),
                'plan_name' => $invoice->subscriptionPlan->name,
                'billing_cycle' => $invoice->billing_cycle,
            ],
            'bankAccounts' => config('subscription.bank_accounts'),
            'existingProofs' => $existingProofs,
            'maxFileSize' => config('subscription.payment_proof.max_size'),
            'allowedTypes' => config('subscription.payment_proof.allowed_types'),
        ]);
    }

    /**
     * Upload payment proof.
     */
    public function uploadPaymentProof(Invoice $invoice, Request $request)
    {
        $company = auth()->user()->company;

        // Ensure invoice belongs to company
        if ($invoice->company_id !== $company->id) {
            abort(403, 'Unauthorized');
        }

        $maxSize = config('subscription.payment_proof.max_size');
        $allowedTypes = implode(',', config('subscription.payment_proof.allowed_types'));

        $validated = $request->validate([
            'file' => "required|file|mimes:{$allowedTypes}|max:{$maxSize}",
            'bank_name' => 'required|string|max:100',
            'account_name' => 'required|string|max:150',
            'account_number' => 'required|string|max:50',
            'transfer_date' => 'required|date',
            'transfer_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $proof = $this->invoiceService->uploadPaymentProof(
            $invoice,
            $request->file('file'),
            [
                'bank_name' => $validated['bank_name'],
                'account_name' => $validated['account_name'],
                'account_number' => $validated['account_number'],
                'transfer_date' => $validated['transfer_date'],
                'transfer_amount' => $validated['transfer_amount'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Payment proof uploaded successfully. We will verify your payment shortly.',
            'proof_id' => $proof->id,
        ]);
    }

    /**
     * Show billing history.
     */
    public function billingHistory(Request $request)
    {
        $company = auth()->user()->company;

        $invoices = $this->invoiceService->getCompanyInvoices($company, [
            'status' => $request->input('status'),
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'per_page' => 15,
        ]);

        return Inertia::render('Subscription/BillingHistory', [
            'invoices' => $invoices->through(fn($invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'amount' => $invoice->amount,
                'formatted_amount' => $invoice->formatted_amount,
                'status' => $invoice->status,
                'status_label' => $invoice->status_label,
                'status_badge' => $invoice->status_badge,
                'billing_cycle' => $invoice->billing_cycle,
                'plan_name' => $invoice->subscriptionPlan->name,
                'due_date' => $invoice->due_date->format('d M Y'),
                'paid_at' => $invoice->paid_at?->format('d M Y'),
                'created_at' => $invoice->created_at->format('d M Y'),
            ]),
            'filters' => $request->only(['status', 'from_date', 'to_date']),
        ]);
    }

    /**
     * Cancel subscription.
     */
    public function cancelSubscription(Request $request)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $company = auth()->user()->company;

        $this->subscriptionService->cancelSubscription($company, $validated['reason'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Your subscription has been cancelled.',
        ]);
    }

    /**
     * Download invoice PDF.
     */
    public function downloadInvoice(Invoice $invoice)
    {
        $company = auth()->user()->company;

        // Ensure invoice belongs to company
        if ($invoice->company_id !== $company->id) {
            abort(403, 'Unauthorized');
        }

        // Load relationships
        $invoice->load(['subscriptionPlan', 'company']);

        // Generate PDF using DomPDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.subscription-invoice', [
            'invoice' => $invoice,
            'company' => $company,
        ]);

        // Set paper size
        $pdf->setPaper('A4', 'portrait');

        // Return PDF download
        $filename = 'Invoice-' . $invoice->invoice_number . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Initiate Stripe checkout for an invoice.
     */
    public function stripeCheckout(Invoice $invoice, StripeService $stripeService)
    {
        $company = auth()->user()->company;

        // Ensure invoice belongs to company
        if ($invoice->company_id !== $company->id) {
            abort(403, 'Unauthorized');
        }

        // Ensure invoice is pending
        if (!$invoice->isPending()) {
            return redirect()->route('subscription.index')
                ->with('error', 'This invoice has already been processed.');
        }

        try {
            $session = $stripeService->createCheckoutSession($invoice);
            return redirect($session->url);
        } catch (\Exception $e) {
            return redirect()->route('subscription.checkout', $invoice->subscription_plan_id)
                ->with('error', 'Failed to create checkout session. Please try again.');
        }
    }

    /**
     * Handle successful payment return.
     */
    public function paymentSuccess(Request $request, StripeService $stripeService)
    {
        $sessionId = $request->input('session_id');
        $invoiceId = $request->input('invoice_id');

        $company = auth()->user()->company;

        // Verify the payment if session ID is provided
        if ($sessionId && $invoiceId) {
            $isValid = $stripeService->verifyPayment($sessionId, (int) $invoiceId);

            if (!$isValid) {
                return Inertia::render('Subscription/PaymentResult', [
                    'success' => false,
                    'title' => 'Payment Verification Failed',
                    'message' => 'We could not verify your payment. Please contact support if you believe this is an error.',
                ]);
            }
        }

        return Inertia::render('Subscription/PaymentResult', [
            'success' => true,
            'title' => 'Payment Successful!',
            'message' => 'Thank you for your subscription! Your account has been activated.',
            'company' => [
                'name' => $company->name,
                'subscription_status' => $company->fresh()->subscription_status,
            ],
        ]);
    }

    /**
     * Handle cancelled payment return.
     */
    public function paymentCancelled(Request $request)
    {
        $invoiceId = $request->input('invoice_id');

        return Inertia::render('Subscription/PaymentResult', [
            'success' => false,
            'title' => 'Payment Cancelled',
            'message' => 'Your payment was cancelled. You can try again anytime.',
            'invoiceId' => $invoiceId,
        ]);
    }
}

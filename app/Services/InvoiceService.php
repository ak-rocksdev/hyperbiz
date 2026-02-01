<?php

namespace App\Services;

use App\Mail\Subscription\PaymentApproved;
use App\Mail\Subscription\PaymentProofPendingAdmin;
use App\Mail\Subscription\PaymentProofReceived;
use App\Mail\Subscription\PaymentRejected;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\PaymentProof;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class InvoiceService
{
    /**
     * Get invoice by ID with relationships.
     */
    public function getInvoice(int $invoiceId): ?Invoice
    {
        return Invoice::with(['company', 'subscriptionPlan', 'paymentTransactions.paymentProof'])
            ->find($invoiceId);
    }

    /**
     * Get invoices for a company.
     */
    public function getCompanyInvoices(Company $company, array $filters = [])
    {
        $query = Invoice::forCompany($company->id)
            ->with(['subscriptionPlan', 'paymentTransactions'])
            ->latest();

        if (!empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        return $query->paginate($filters['per_page'] ?? 10);
    }

    /**
     * Get pending invoices for a company.
     */
    public function getPendingInvoices(Company $company)
    {
        return Invoice::forCompany($company->id)
            ->unpaid()
            ->with(['subscriptionPlan', 'paymentTransactions'])
            ->latest()
            ->get();
    }

    /**
     * Mark invoice as awaiting verification (for bank transfers).
     */
    public function markAwaitingVerification(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            $invoice->update([
                'status' => Invoice::STATUS_AWAITING_VERIFICATION,
            ]);

            $invoice->paymentTransactions()->update([
                'status' => PaymentTransaction::STATUS_AWAITING_VERIFICATION,
            ]);

            return $invoice->fresh();
        });
    }

    /**
     * Upload payment proof for an invoice.
     */
    public function uploadPaymentProof(
        Invoice $invoice,
        UploadedFile $file,
        array $transferDetails
    ): PaymentProof {
        return DB::transaction(function () use ($invoice, $file, $transferDetails) {
            // Get or create payment transaction
            $transaction = $invoice->paymentTransactions()->latest()->first();

            if (!$transaction) {
                $transaction = PaymentTransaction::create([
                    'company_id' => $invoice->company_id,
                    'invoice_id' => $invoice->id,
                    'transaction_id' => PaymentTransaction::generateTransactionId(),
                    'amount' => $invoice->amount,
                    'currency' => $invoice->currency,
                    'payment_method' => PaymentTransaction::METHOD_BANK_TRANSFER,
                    'status' => PaymentTransaction::STATUS_AWAITING_VERIFICATION,
                ]);
            }

            // Store file
            $path = $file->store(
                config('subscription.payment_proof.storage_path', 'payment-proofs'),
                'public'
            );

            // Create payment proof
            $proof = PaymentProof::create([
                'payment_transaction_id' => $transaction->id,
                'company_id' => $invoice->company_id,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => round($file->getSize() / 1024), // Convert to KB
                'file_type' => $file->getClientOriginalExtension(),
                'bank_name' => $transferDetails['bank_name'],
                'account_name' => $transferDetails['account_name'],
                'account_number' => $transferDetails['account_number'],
                'transfer_date' => $transferDetails['transfer_date'],
                'transfer_amount' => $transferDetails['transfer_amount'],
                'notes' => $transferDetails['notes'] ?? null,
                'status' => PaymentProof::STATUS_PENDING,
            ]);

            // Update invoice and transaction status
            $invoice->update([
                'status' => Invoice::STATUS_AWAITING_VERIFICATION,
            ]);

            $transaction->update([
                'status' => PaymentTransaction::STATUS_AWAITING_VERIFICATION,
            ]);

            Log::info("Payment proof uploaded for invoice {$invoice->invoice_number}", [
                'company_id' => $invoice->company_id,
                'proof_id' => $proof->id,
            ]);

            // Send email notifications
            $this->sendPaymentProofNotifications($proof);

            return $proof;
        });
    }

    /**
     * Send email notifications for payment proof upload.
     */
    protected function sendPaymentProofNotifications(PaymentProof $proof): void
    {
        $transaction = $proof->paymentTransaction;
        $invoice = $transaction->invoice;
        $company = $invoice->company;

        // Send confirmation to tenant
        if ($company->email) {
            Mail::to($company->email)->queue(new PaymentProofReceived($proof));
        }

        // Notify platform admins
        $adminEmail = config('subscription.admin_email');
        if ($adminEmail) {
            Mail::to($adminEmail)->queue(new PaymentProofPendingAdmin($proof));
        }
    }

    /**
     * Approve payment proof and activate subscription.
     */
    public function approvePaymentProof(PaymentProof $proof, int $reviewerId): bool
    {
        return DB::transaction(function () use ($proof, $reviewerId) {
            // Approve the proof
            $proof->approve($reviewerId);

            // Get the transaction and invoice
            $transaction = $proof->paymentTransaction;
            $invoice = $transaction->invoice;
            $company = $invoice->company;
            $plan = $invoice->subscriptionPlan;

            // Update transaction status
            $transaction->update([
                'status' => PaymentTransaction::STATUS_SUCCESS,
            ]);

            // Update invoice status
            $invoice->update([
                'status' => Invoice::STATUS_PAID,
                'paid_at' => now(),
            ]);

            // Activate subscription
            $subscriptionService = app(SubscriptionService::class);
            $subscriptionService->activateSubscription(
                $company,
                $plan,
                $invoice->billing_cycle
            );

            Log::info("Payment proof approved for invoice {$invoice->invoice_number}", [
                'company_id' => $company->id,
                'reviewer_id' => $reviewerId,
            ]);

            // Send approval email to tenant
            if ($company->email) {
                Mail::to($company->email)->queue(new PaymentApproved($proof));
            }

            return true;
        });
    }

    /**
     * Reject payment proof.
     */
    public function rejectPaymentProof(PaymentProof $proof, int $reviewerId, string $reason): bool
    {
        return DB::transaction(function () use ($proof, $reviewerId, $reason) {
            // Reject the proof
            $proof->reject($reviewerId, $reason);

            // Update transaction status
            $proof->paymentTransaction->update([
                'status' => PaymentTransaction::STATUS_REJECTED,
                'failure_reason' => $reason,
            ]);

            // Keep invoice as pending so user can re-upload
            $invoice = $proof->paymentTransaction->invoice;
            $invoice->update([
                'status' => Invoice::STATUS_PENDING,
            ]);

            Log::info("Payment proof rejected for invoice {$invoice->invoice_number}", [
                'company_id' => $invoice->company_id,
                'reviewer_id' => $reviewerId,
                'reason' => $reason,
            ]);

            // Send rejection email to tenant
            $company = $invoice->company;
            if ($company->email) {
                Mail::to($company->email)->queue(new PaymentRejected($proof));
            }

            return true;
        });
    }

    /**
     * Get pending payment proofs for admin verification.
     */
    public function getPendingPaymentProofs(array $filters = [])
    {
        $query = PaymentProof::pending()
            ->with([
                'paymentTransaction.invoice.company',
                'paymentTransaction.invoice.subscriptionPlan',
                'company',
            ])
            ->latest();

        if (!empty($filters['company_id'])) {
            $query->forCompany($filters['company_id']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Cancel an invoice.
     */
    public function cancelInvoice(Invoice $invoice, string $reason = null): Invoice
    {
        return DB::transaction(function () use ($invoice, $reason) {
            $invoice->update([
                'status' => Invoice::STATUS_CANCELLED,
                'notes' => $reason,
            ]);

            $invoice->paymentTransactions()->update([
                'status' => PaymentTransaction::STATUS_FAILED,
                'failure_reason' => $reason ?? 'Invoice cancelled',
            ]);

            return $invoice->fresh();
        });
    }

    /**
     * Expire overdue invoices.
     */
    public function expireOverdueInvoices(): int
    {
        $expiredCount = 0;

        $overdueInvoices = Invoice::where('status', Invoice::STATUS_PENDING)
            ->whereDate('due_date', '<', Carbon::now())
            ->get();

        foreach ($overdueInvoices as $invoice) {
            $invoice->update([
                'status' => Invoice::STATUS_EXPIRED,
            ]);

            $invoice->paymentTransactions()->update([
                'status' => PaymentTransaction::STATUS_EXPIRED,
            ]);

            $expiredCount++;
        }

        if ($expiredCount > 0) {
            Log::info("Expired {$expiredCount} overdue invoices");
        }

        return $expiredCount;
    }

    /**
     * Get invoice statistics for admin dashboard.
     */
    public function getInvoiceStats(): array
    {
        return [
            'total' => Invoice::count(),
            'pending' => Invoice::byStatus(Invoice::STATUS_PENDING)->count(),
            'awaiting_verification' => Invoice::byStatus(Invoice::STATUS_AWAITING_VERIFICATION)->count(),
            'paid' => Invoice::byStatus(Invoice::STATUS_PAID)->count(),
            'failed' => Invoice::byStatus(Invoice::STATUS_FAILED)->count(),
            'total_revenue' => Invoice::byStatus(Invoice::STATUS_PAID)->sum('amount'),
            'monthly_revenue' => Invoice::byStatus(Invoice::STATUS_PAID)
                ->whereMonth('paid_at', Carbon::now()->month)
                ->sum('amount'),
        ];
    }
}

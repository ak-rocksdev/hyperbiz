<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentProof;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentVerificationController extends Controller
{
    protected InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Ensure only platform admins can access these methods.
     */
    private function ensurePlatformAdmin(): void
    {
        if (!auth()->user()?->isPlatformAdmin()) {
            abort(403, 'Access denied. Platform admin only.');
        }
    }

    /**
     * Display list of pending payment proofs.
     */
    public function index(Request $request)
    {
        $this->ensurePlatformAdmin();

        $query = PaymentProof::with([
            'paymentTransaction.invoice.subscriptionPlan',
            'company',
            'reviewer',
        ])->latest();

        // Filter by status (show all when no status filter is provided)
        $status = $request->input('status');
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        // Search by company name or invoice number
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('company', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%");
                })->orWhereHas('paymentTransaction.invoice', function ($q2) use ($search) {
                    $q2->where('invoice_number', 'like', "%{$search}%");
                });
            });
        }

        $proofs = $query->paginate(15)->through(fn($proof) => [
            'id' => $proof->id,
            'company' => [
                'id' => $proof->company->id,
                'name' => $proof->company->name,
            ],
            'invoice' => [
                'id' => $proof->paymentTransaction->invoice->id,
                'invoice_number' => $proof->paymentTransaction->invoice->invoice_number,
                'amount' => $proof->paymentTransaction->invoice->amount,
                'formatted_amount' => $proof->paymentTransaction->invoice->formatted_amount,
                'plan_name' => $proof->paymentTransaction->invoice->subscriptionPlan->name,
                'billing_cycle' => $proof->paymentTransaction->invoice->billing_cycle,
            ],
            'file_name' => $proof->file_name,
            'file_url' => $proof->file_url,
            'file_type' => $proof->file_type,
            'bank_name' => $proof->bank_name,
            'account_name' => $proof->account_name,
            'account_number' => $proof->account_number,
            'transfer_date' => $proof->transfer_date->format('d M Y'),
            'transfer_amount' => $proof->transfer_amount,
            'formatted_transfer_amount' => $proof->formatted_transfer_amount,
            'notes' => $proof->notes,
            'status' => $proof->status,
            'status_label' => $proof->status_label,
            'status_badge' => $proof->status_badge,
            'rejection_reason' => $proof->rejection_reason,
            'reviewer' => $proof->reviewer ? [
                'id' => $proof->reviewer->id,
                'name' => $proof->reviewer->name,
            ] : null,
            'reviewed_at' => $proof->reviewed_at?->format('d M Y H:i'),
            'created_at' => $proof->created_at->format('d M Y H:i'),
        ]);

        // Stats
        $stats = [
            'pending' => PaymentProof::pending()->count(),
            'approved' => PaymentProof::approved()->count(),
            'rejected' => PaymentProof::rejected()->count(),
            'total' => PaymentProof::count(),
        ];

        return Inertia::render('Admin/PaymentVerifications/Index', [
            'proofs' => $proofs,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Show payment proof details.
     */
    public function show(PaymentProof $proof)
    {
        $this->ensurePlatformAdmin();

        $proof->load([
            'paymentTransaction.invoice.subscriptionPlan',
            'company',
            'reviewer',
        ]);

        return response()->json([
            'proof' => [
                'id' => $proof->id,
                'company' => [
                    'id' => $proof->company->id,
                    'name' => $proof->company->name,
                    'email' => $proof->company->email,
                ],
                'invoice' => [
                    'id' => $proof->paymentTransaction->invoice->id,
                    'invoice_number' => $proof->paymentTransaction->invoice->invoice_number,
                    'amount' => $proof->paymentTransaction->invoice->amount,
                    'formatted_amount' => $proof->paymentTransaction->invoice->formatted_amount,
                    'plan_name' => $proof->paymentTransaction->invoice->subscriptionPlan->name,
                    'billing_cycle' => $proof->paymentTransaction->invoice->billing_cycle,
                    'due_date' => $proof->paymentTransaction->invoice->due_date->format('d M Y'),
                ],
                'file_name' => $proof->file_name,
                'file_url' => $proof->file_url,
                'file_type' => $proof->file_type,
                'formatted_file_size' => $proof->formatted_file_size,
                'bank_name' => $proof->bank_name,
                'account_name' => $proof->account_name,
                'account_number' => $proof->account_number,
                'transfer_date' => $proof->transfer_date->format('d M Y'),
                'transfer_amount' => $proof->transfer_amount,
                'formatted_transfer_amount' => $proof->formatted_transfer_amount,
                'notes' => $proof->notes,
                'status' => $proof->status,
                'status_label' => $proof->status_label,
                'rejection_reason' => $proof->rejection_reason,
                'reviewer' => $proof->reviewer ? [
                    'id' => $proof->reviewer->id,
                    'name' => $proof->reviewer->name,
                ] : null,
                'reviewed_at' => $proof->reviewed_at?->format('d M Y H:i'),
                'created_at' => $proof->created_at->format('d M Y H:i'),
            ],
        ]);
    }

    /**
     * Approve payment proof.
     */
    public function approve(PaymentProof $proof)
    {
        $this->ensurePlatformAdmin();

        if (!$proof->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'This payment proof has already been processed.',
            ], 422);
        }

        $this->invoiceService->approvePaymentProof($proof, auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Payment proof approved successfully. Subscription has been activated.',
        ]);
    }

    /**
     * Reject payment proof.
     */
    public function reject(PaymentProof $proof, Request $request)
    {
        $this->ensurePlatformAdmin();

        if (!$proof->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'This payment proof has already been processed.',
            ], 422);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $this->invoiceService->rejectPaymentProof($proof, auth()->id(), $validated['reason']);

        return response()->json([
            'success' => true,
            'message' => 'Payment proof rejected. The customer has been notified.',
        ]);
    }
}

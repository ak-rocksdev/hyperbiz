<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\Currency;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Display a list of payments.
     */
    public function list(Request $request)
    {
        $search = $request->input('search');
        $paymentType = $request->input('payment_type');
        $paymentMethod = $request->input('payment_method');
        $dateRange = $request->input('date_range');
        $perPage = $request->input('per_page', 10);

        $query = Payment::with('createdBy')
            ->orderByDesc('id'); // Most recently recorded first

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        if ($paymentType) {
            $query->where('payment_type', $paymentType);
        }

        if ($paymentMethod) {
            $query->where('payment_method', $paymentMethod);
        }

        // Date range filter (format: "YYYY-MM-DD - YYYY-MM-DD")
        if ($dateRange && str_contains($dateRange, ' - ')) {
            [$startDate, $endDate] = explode(' - ', $dateRange);
            $query->whereBetween('payment_date', [trim($startDate), trim($endDate)]);
        }

        $payments = $query->paginate($perPage);

        $data = $payments->getCollection()->map(function ($payment) {
            // Get parent reference
            $reference = null;
            if ($payment->reference_type === 'purchase_order') {
                $po = PurchaseOrder::find($payment->reference_id);
                $reference = $po ? $po->po_number : 'N/A';
            } elseif ($payment->reference_type === 'sales_order') {
                $so = SalesOrder::find($payment->reference_id);
                $reference = $so ? $so->so_number : 'N/A';
            }

            return [
                'id' => $payment->id,
                'payment_number' => $payment->payment_number,
                'payment_type' => $payment->payment_type,
                'reference_type' => $payment->reference_type,
                'reference_number' => $reference,
                'payment_date' => Carbon::parse($payment->payment_date)->format('d M Y'),
                'currency_code' => $payment->currency_code,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'payment_method_label' => $payment->payment_method_label,
                'status' => $payment->status,
                'created_by' => $payment->createdBy->name ?? 'System',
            ];
        });

        $stats = [
            'total_payments' => Payment::where('status', 'confirmed')->count(),
            'purchase_payments' => Payment::where('payment_type', 'purchase')->where('status', 'confirmed')->sum('amount'),
            'sales_payments' => Payment::where('payment_type', 'sales')->where('status', 'confirmed')->sum('amount'),
        ];

        return Inertia::render('Payment/List', [
            'payments' => $data,
            'pagination' => [
                'total' => $payments->total(),
                'per_page' => $payments->perPage(),
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
            ],
            'stats' => $stats,
            'filters' => [
                'search' => $search,
                'payment_type' => $paymentType,
                'payment_method' => $paymentMethod,
                'date_range' => $dateRange,
            ],
            'paymentMethods' => [
                ['value' => 'cash', 'label' => 'Cash'],
                ['value' => 'bank_transfer', 'label' => 'Bank Transfer'],
                ['value' => 'credit_card', 'label' => 'Credit Card'],
                ['value' => 'debit_card', 'label' => 'Debit Card'],
                ['value' => 'cheque', 'label' => 'Cheque'],
                ['value' => 'giro', 'label' => 'Giro'],
                ['value' => 'e_wallet', 'label' => 'E-Wallet'],
                ['value' => 'other', 'label' => 'Other'],
            ],
        ]);
    }

    /**
     * Store payment for Purchase Order.
     */
    public function storeForPurchaseOrder(Request $request, $purchaseOrderId)
    {
        $po = PurchaseOrder::findOrFail($purchaseOrderId);

        $amountDue = $po->grand_total - $po->amount_paid;

        $validated = $request->validate([
            'payment_date' => 'required|date',
            'amount' => "required|numeric|min:0.01|max:{$amountDue}",
            'payment_method' => 'required|in:cash,bank_transfer,credit_card,debit_card,cheque,giro,e_wallet,other',
            'bank_name' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ], [
            'amount.max' => "Payment amount cannot exceed the amount due ({$amountDue}).",
        ]);

        DB::beginTransaction();

        try {
            $payment = Payment::create([
                'payment_type' => Payment::TYPE_PURCHASE,
                'reference_type' => 'purchase_order',
                'reference_id' => $po->id,
                'payment_date' => $validated['payment_date'],
                'currency_code' => $po->currency_code,
                'exchange_rate' => $po->exchange_rate,
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'bank_name' => $validated['bank_name'] ?? null,
                'account_number' => $validated['account_number'] ?? null,
                'reference_number' => $validated['reference_number'] ?? null,
                'status' => Payment::STATUS_CONFIRMED,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Update PO payment status
            $po->updatePaymentStatus();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully.',
                'data' => [
                    'payment_number' => $payment->payment_number,
                    'payment_status' => $po->payment_status,
                    'amount_paid' => $po->amount_paid,
                    'amount_due' => $po->grand_total - $po->amount_paid,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to record payment.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Store payment for Sales Order.
     */
    public function storeForSalesOrder(Request $request, $salesOrderId)
    {
        $so = SalesOrder::findOrFail($salesOrderId);

        $amountDue = $so->grand_total - $so->amount_paid;

        $validated = $request->validate([
            'payment_date' => 'required|date',
            'amount' => "required|numeric|min:0.01|max:{$amountDue}",
            'payment_method' => 'required|in:cash,bank_transfer,credit_card,debit_card,cheque,giro,e_wallet,other',
            'bank_name' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ], [
            'amount.max' => "Payment amount cannot exceed the amount due ({$amountDue}).",
        ]);

        DB::beginTransaction();

        try {
            $payment = Payment::create([
                'payment_type' => Payment::TYPE_SALES,
                'reference_type' => 'sales_order',
                'reference_id' => $so->id,
                'payment_date' => $validated['payment_date'],
                'currency_code' => $so->currency_code,
                'exchange_rate' => $so->exchange_rate,
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'bank_name' => $validated['bank_name'] ?? null,
                'account_number' => $validated['account_number'] ?? null,
                'reference_number' => $validated['reference_number'] ?? null,
                'status' => Payment::STATUS_CONFIRMED,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Update SO payment status
            $so->updatePaymentStatus();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully.',
                'data' => [
                    'payment_number' => $payment->payment_number,
                    'payment_status' => $so->payment_status,
                    'amount_paid' => $so->amount_paid,
                    'amount_due' => $so->grand_total - $so->amount_paid,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to record payment.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Show payment details.
     */
    public function show($id)
    {
        $payment = Payment::with('createdBy')->findOrFail($id);

        // Get parent reference details
        $reference = null;
        if ($payment->reference_type === 'purchase_order') {
            $po = PurchaseOrder::with('supplier')->find($payment->reference_id);
            if ($po) {
                $reference = [
                    'type' => 'Purchase Order',
                    'id' => $po->id,
                    'number' => $po->po_number,
                    'party_name' => $po->supplier->client_name ?? 'N/A',
                    'grand_total' => $po->grand_total,
                ];
            }
        } elseif ($payment->reference_type === 'sales_order') {
            $so = SalesOrder::with('customer')->find($payment->reference_id);
            if ($so) {
                $reference = [
                    'type' => 'Sales Order',
                    'id' => $so->id,
                    'number' => $so->so_number,
                    'party_name' => $so->customer->client_name ?? 'N/A',
                    'grand_total' => $so->grand_total,
                ];
            }
        }

        $data = [
            'id' => $payment->id,
            'payment_number' => $payment->payment_number,
            'payment_type' => $payment->payment_type,
            'reference' => $reference,
            'payment_date' => Carbon::parse($payment->payment_date)->format('d M Y'),
            'currency_code' => $payment->currency_code,
            'amount' => $payment->amount,
            'payment_method' => $payment->payment_method,
            'payment_method_label' => $payment->payment_method_label,
            'bank_name' => $payment->bank_name,
            'account_number' => $payment->account_number,
            'reference_number' => $payment->reference_number,
            'status' => $payment->status,
            'notes' => $payment->notes,
            'created_by' => $payment->createdBy->name ?? 'System',
            'created_at' => Carbon::parse($payment->created_at)->format('d M Y H:i'),
        ];

        return Inertia::render('Payment/Detail', [
            'payment' => $data,
        ]);
    }

    /**
     * Cancel payment.
     */
    public function cancel($id)
    {
        $payment = Payment::findOrFail($id);

        if ($payment->status === Payment::STATUS_CANCELLED) {
            return response()->json([
                'success' => false,
                'message' => 'Payment is already cancelled.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            $payment->status = Payment::STATUS_CANCELLED;
            $payment->save();

            // Update parent payment status
            if ($payment->reference_type === 'purchase_order') {
                $po = PurchaseOrder::find($payment->reference_id);
                if ($po) {
                    $po->updatePaymentStatus();
                }
            } elseif ($payment->reference_type === 'sales_order') {
                $so = SalesOrder::find($payment->reference_id);
                if ($so) {
                    $so->updatePaymentStatus();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment cancelled successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel payment.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get payments for a Purchase Order.
     */
    public function getForPurchaseOrder($purchaseOrderId)
    {
        $payments = Payment::where('reference_type', 'purchase_order')
            ->where('reference_id', $purchaseOrderId)
            ->orderByDesc('payment_date')
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'payment_date' => Carbon::parse($payment->payment_date)->format('d M Y'),
                    'amount' => $payment->amount,
                    'payment_method' => $payment->payment_method_label,
                    'status' => $payment->status,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    /**
     * Get payments for a Sales Order.
     */
    public function getForSalesOrder($salesOrderId)
    {
        $payments = Payment::where('reference_type', 'sales_order')
            ->where('reference_id', $salesOrderId)
            ->orderByDesc('payment_date')
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'payment_date' => Carbon::parse($payment->payment_date)->format('d M Y'),
                    'amount' => $payment->amount,
                    'payment_method' => $payment->payment_method_label,
                    'status' => $payment->status,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }
}

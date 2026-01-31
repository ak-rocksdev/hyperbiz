<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\SupplierBalance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * AP Aging Service - Calculates Accounts Payable aging
 *
 * Aging is calculated based on purchase orders with outstanding balances.
 * Uses the order_date (or expected_date if available) as the aging reference.
 */
class APAgingService
{
    /**
     * Aging bucket days
     */
    protected array $agingBuckets = [
        'current' => [0, 30],
        '31_60' => [31, 60],
        '61_90' => [61, 90],
        'over_90' => [91, 999999],
    ];

    /**
     * Get AP aging report data
     *
     * @param array $filters
     * @return array
     */
    public function getAgingReport(array $filters = []): array
    {
        $asOfDate = Carbon::parse($filters['as_of_date'] ?? now())->endOfDay();
        $currency = $filters['currency'] ?? 'IDR';
        $supplierId = $filters['supplier_id'] ?? null;

        // Get all suppliers with outstanding purchase orders
        $query = DB::table('purchase_orders as po')
            ->join('mst_client as s', 'po.supplier_id', '=', 's.id')
            ->select(
                's.id as supplier_id',
                's.client_name as supplier_name',
                's.email',
                'po.id as order_id',
                'po.po_number',
                'po.order_date',
                'po.expected_date',
                'po.grand_total',
                'po.amount_paid',
                DB::raw('(po.grand_total - po.amount_paid) as balance_due'),
                'po.currency_code'
            )
            ->where('po.currency_code', $currency)
            ->where('po.payment_status', '!=', 'paid')
            ->whereIn('po.status', ['confirmed', 'partial', 'received'])
            ->where('po.order_date', '<=', $asOfDate);

        if ($supplierId) {
            $query->where('s.id', $supplierId);
        }

        $orders = $query->get();

        // Calculate aging for each order
        $agingData = [];
        $totals = [
            'current_0_30' => 0,
            'current_31_60' => 0,
            'current_61_90' => 0,
            'current_over_90' => 0,
            'total_balance' => 0,
        ];

        foreach ($orders as $order) {
            if (!isset($agingData[$order->supplier_id])) {
                $agingData[$order->supplier_id] = [
                    'supplier_id' => $order->supplier_id,
                    'supplier_name' => $order->supplier_name,
                    'email' => $order->email,
                    'current_0_30' => 0,
                    'current_31_60' => 0,
                    'current_61_90' => 0,
                    'current_over_90' => 0,
                    'total_balance' => 0,
                    'orders' => [],
                ];
            }

            $referenceDate = $order->expected_date ?? $order->order_date;
            $daysPastDue = Carbon::parse($referenceDate)->diffInDays($asOfDate, false);
            $balance = (float) $order->balance_due;

            if ($balance <= 0) {
                continue;
            }

            // Categorize by aging bucket
            $bucket = $this->getAgingBucket($daysPastDue);
            $agingData[$order->supplier_id][$bucket] += $balance;
            $agingData[$order->supplier_id]['total_balance'] += $balance;
            $totals[$bucket] += $balance;
            $totals['total_balance'] += $balance;

            // Add order detail
            $agingData[$order->supplier_id]['orders'][] = [
                'order_id' => $order->order_id,
                'po_number' => $order->po_number,
                'order_date' => $order->order_date,
                'expected_date' => $order->expected_date,
                'grand_total' => $order->grand_total,
                'amount_paid' => $order->amount_paid,
                'balance_due' => $balance,
                'days_past_due' => $daysPastDue,
                'bucket' => $bucket,
            ];
        }

        // Sort by total balance descending
        usort($agingData, fn($a, $b) => $b['total_balance'] <=> $a['total_balance']);

        return [
            'as_of_date' => $asOfDate->format('Y-m-d'),
            'currency' => $currency,
            'suppliers' => array_values($agingData),
            'totals' => $totals,
            'summary' => [
                'total_suppliers' => count($agingData),
                'total_orders' => $orders->count(),
            ],
        ];
    }

    /**
     * Get aging bucket key based on days past due
     */
    protected function getAgingBucket(int $daysPastDue): string
    {
        if ($daysPastDue <= 30) {
            return 'current_0_30';
        } elseif ($daysPastDue <= 60) {
            return 'current_31_60';
        } elseif ($daysPastDue <= 90) {
            return 'current_61_90';
        }

        return 'current_over_90';
    }

    /**
     * Update supplier balance record
     */
    public function updateSupplierBalance(int $supplierId, string $currency = 'IDR'): SupplierBalance
    {
        $agingData = $this->getAgingReport([
            'supplier_id' => $supplierId,
            'currency' => $currency,
        ]);

        $supplierData = $agingData['suppliers'][0] ?? null;
        $balance = SupplierBalance::getOrCreate($supplierId, $currency);

        if ($supplierData) {
            $balance->update([
                'current_0_30' => $supplierData['current_0_30'],
                'current_31_60' => $supplierData['current_31_60'],
                'current_61_90' => $supplierData['current_61_90'],
                'current_over_90' => $supplierData['current_over_90'],
                'current_balance' => $supplierData['total_balance'],
            ]);
        } else {
            $balance->update([
                'current_0_30' => 0,
                'current_31_60' => 0,
                'current_61_90' => 0,
                'current_over_90' => 0,
                'current_balance' => 0,
            ]);
        }

        return $balance->fresh();
    }

    /**
     * Recalculate all supplier balances
     */
    public function recalculateAllBalances(string $currency = 'IDR'): int
    {
        $agingData = $this->getAgingReport(['currency' => $currency]);

        $updatedCount = 0;
        foreach ($agingData['suppliers'] as $supplier) {
            $balance = SupplierBalance::getOrCreate($supplier['supplier_id'], $currency);
            $balance->update([
                'current_0_30' => $supplier['current_0_30'],
                'current_31_60' => $supplier['current_31_60'],
                'current_61_90' => $supplier['current_61_90'],
                'current_over_90' => $supplier['current_over_90'],
                'current_balance' => $supplier['total_balance'],
            ]);
            $updatedCount++;
        }

        // Reset balances for suppliers no longer in the report
        SupplierBalance::where('currency_code', $currency)
            ->whereNotIn('supplier_id', array_column($agingData['suppliers'], 'supplier_id'))
            ->update([
                'current_0_30' => 0,
                'current_31_60' => 0,
                'current_61_90' => 0,
                'current_over_90' => 0,
                'current_balance' => 0,
            ]);

        return $updatedCount;
    }

    /**
     * Get supplier AP summary
     */
    public function getSupplierSummary(int $supplierId, string $currency = 'IDR'): array
    {
        $balance = SupplierBalance::forSupplier($supplierId)
            ->forCurrency($currency)
            ->first();

        $agingData = $this->getAgingReport([
            'supplier_id' => $supplierId,
            'currency' => $currency,
        ]);

        return [
            'balance' => $balance,
            'aging_detail' => $agingData['suppliers'][0] ?? null,
        ];
    }
}

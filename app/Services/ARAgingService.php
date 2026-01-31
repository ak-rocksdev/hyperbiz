<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerBalance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * AR Aging Service - Calculates Accounts Receivable aging
 *
 * Aging is calculated based on sales orders with outstanding balances.
 * Uses the order_date (or due_date if available) as the aging reference.
 */
class ARAgingService
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
     * Get AR aging report data
     *
     * @param array $filters
     * @return array
     */
    public function getAgingReport(array $filters = []): array
    {
        $asOfDate = Carbon::parse($filters['as_of_date'] ?? now())->endOfDay();
        $currency = $filters['currency'] ?? 'IDR';
        $customerId = $filters['customer_id'] ?? null;

        // Get all customers with outstanding sales orders
        $query = DB::table('sales_orders as so')
            ->join('mst_client as c', 'so.customer_id', '=', 'c.id')
            ->select(
                'c.id as customer_id',
                'c.client_name as customer_name',
                'c.email',
                'so.id as order_id',
                'so.so_number',
                'so.order_date',
                'so.due_date',
                'so.grand_total',
                'so.amount_paid',
                DB::raw('(so.grand_total - so.amount_paid) as balance_due'),
                'so.currency_code'
            )
            ->where('so.currency_code', $currency)
            ->where('so.payment_status', '!=', 'paid')
            ->whereIn('so.status', ['confirmed', 'processing', 'partial', 'shipped', 'delivered'])
            ->where('so.order_date', '<=', $asOfDate);

        if ($customerId) {
            $query->where('c.id', $customerId);
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
            if (!isset($agingData[$order->customer_id])) {
                $agingData[$order->customer_id] = [
                    'customer_id' => $order->customer_id,
                    'customer_name' => $order->customer_name,
                    'email' => $order->email,
                    'current_0_30' => 0,
                    'current_31_60' => 0,
                    'current_61_90' => 0,
                    'current_over_90' => 0,
                    'total_balance' => 0,
                    'orders' => [],
                ];
            }

            $referenceDate = $order->due_date ?? $order->order_date;
            $daysPastDue = Carbon::parse($referenceDate)->diffInDays($asOfDate, false);
            $balance = (float) $order->balance_due;

            if ($balance <= 0) {
                continue;
            }

            // Categorize by aging bucket
            $bucket = $this->getAgingBucket($daysPastDue);
            $agingData[$order->customer_id][$bucket] += $balance;
            $agingData[$order->customer_id]['total_balance'] += $balance;
            $totals[$bucket] += $balance;
            $totals['total_balance'] += $balance;

            // Add order detail
            $agingData[$order->customer_id]['orders'][] = [
                'order_id' => $order->order_id,
                'so_number' => $order->so_number,
                'order_date' => $order->order_date,
                'due_date' => $order->due_date,
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
            'customers' => array_values($agingData),
            'totals' => $totals,
            'summary' => [
                'total_customers' => count($agingData),
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
     * Update customer balance record
     */
    public function updateCustomerBalance(int $customerId, string $currency = 'IDR'): CustomerBalance
    {
        $agingData = $this->getAgingReport([
            'customer_id' => $customerId,
            'currency' => $currency,
        ]);

        $customerData = $agingData['customers'][0] ?? null;
        $balance = CustomerBalance::getOrCreate($customerId, $currency);

        if ($customerData) {
            $balance->update([
                'current_0_30' => $customerData['current_0_30'],
                'current_31_60' => $customerData['current_31_60'],
                'current_61_90' => $customerData['current_61_90'],
                'current_over_90' => $customerData['current_over_90'],
                'current_balance' => $customerData['total_balance'],
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

        $balance->updateAvailableCredit();

        return $balance->fresh();
    }

    /**
     * Recalculate all customer balances
     */
    public function recalculateAllBalances(string $currency = 'IDR'): int
    {
        $agingData = $this->getAgingReport(['currency' => $currency]);

        $updatedCount = 0;
        foreach ($agingData['customers'] as $customer) {
            $balance = CustomerBalance::getOrCreate($customer['customer_id'], $currency);
            $balance->update([
                'current_0_30' => $customer['current_0_30'],
                'current_31_60' => $customer['current_31_60'],
                'current_61_90' => $customer['current_61_90'],
                'current_over_90' => $customer['current_over_90'],
                'current_balance' => $customer['total_balance'],
            ]);
            $balance->updateAvailableCredit();
            $updatedCount++;
        }

        // Reset balances for customers no longer in the report
        CustomerBalance::where('currency_code', $currency)
            ->whereNotIn('customer_id', array_column($agingData['customers'], 'customer_id'))
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
     * Get customer AR summary
     */
    public function getCustomerSummary(int $customerId, string $currency = 'IDR'): array
    {
        $balance = CustomerBalance::forCustomer($customerId)
            ->forCurrency($currency)
            ->first();

        $agingData = $this->getAgingReport([
            'customer_id' => $customerId,
            'currency' => $currency,
        ]);

        return [
            'balance' => $balance,
            'aging_detail' => $agingData['customers'][0] ?? null,
        ];
    }
}

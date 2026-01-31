<?php

namespace App\Http\Controllers;

use App\Services\ARAgingService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class ARAgingController extends Controller implements HasMiddleware
{
    protected ARAgingService $arAgingService;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:finance.reports.ar_aging', only: ['index', 'detail', 'recalculate', 'export']),
        ];
    }

    public function __construct(ARAgingService $arAgingService)
    {
        $this->arAgingService = $arAgingService;
    }

    /**
     * Display AR aging report
     */
    public function index(Request $request)
    {
        $filters = [
            'as_of_date' => $request->get('as_of_date', now()->format('Y-m-d')),
            'currency' => $request->get('currency', 'IDR'),
            'customer_id' => $request->get('customer_id'),
        ];

        $agingReport = $this->arAgingService->getAgingReport($filters);

        // Transform for frontend
        $customers = collect($agingReport['customers'])->map(function ($customer) {
            return [
                'customer_id' => $customer['customer_id'],
                'customer_name' => $customer['customer_name'],
                'email' => $customer['email'],
                'current_0_30' => $customer['current_0_30'],
                'current_31_60' => $customer['current_31_60'],
                'current_61_90' => $customer['current_61_90'],
                'current_over_90' => $customer['current_over_90'],
                'total_balance' => $customer['total_balance'],
                'total_overdue' => $customer['current_31_60'] + $customer['current_61_90'] + $customer['current_over_90'],
                'aging_status' => $this->getAgingStatus($customer),
                'order_count' => count($customer['orders']),
            ];
        });

        return Inertia::render('Finance/Reports/ARAgingReport', [
            'customers' => $customers,
            'totals' => $agingReport['totals'],
            'summary' => $agingReport['summary'],
            'filters' => $filters,
        ]);
    }

    /**
     * Get AR aging detail for a specific customer (API)
     */
    public function detail(Request $request, int $customerId)
    {
        $filters = [
            'as_of_date' => $request->get('as_of_date', now()->format('Y-m-d')),
            'currency' => $request->get('currency', 'IDR'),
            'customer_id' => $customerId,
        ];

        $agingReport = $this->arAgingService->getAgingReport($filters);
        $customerData = $agingReport['customers'][0] ?? null;

        if (!$customerData) {
            return response()->json([
                'success' => false,
                'message' => 'No outstanding balance for this customer.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $customerData,
        ]);
    }

    /**
     * Recalculate all AR balances (API)
     */
    public function recalculate(Request $request)
    {
        $currency = $request->get('currency', 'IDR');
        $updatedCount = $this->arAgingService->recalculateAllBalances($currency);

        return response()->json([
            'success' => true,
            'message' => "Recalculated {$updatedCount} customer balances.",
            'updated_count' => $updatedCount,
        ]);
    }

    /**
     * Export AR aging to Excel/PDF (future)
     */
    public function export(Request $request)
    {
        // TODO: Implement export functionality
        return response()->json([
            'success' => false,
            'message' => 'Export functionality coming soon.',
        ]);
    }

    /**
     * Determine aging status based on balances
     */
    protected function getAgingStatus(array $customer): string
    {
        if ($customer['current_over_90'] > 0) {
            return 'critical';
        } elseif ($customer['current_61_90'] > 0) {
            return 'warning';
        } elseif ($customer['current_31_60'] > 0) {
            return 'attention';
        } elseif ($customer['current_0_30'] > 0) {
            return 'current';
        }

        return 'none';
    }
}

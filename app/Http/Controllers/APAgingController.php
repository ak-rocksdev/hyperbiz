<?php

namespace App\Http\Controllers;

use App\Services\APAgingService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class APAgingController extends Controller implements HasMiddleware
{
    protected APAgingService $apAgingService;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:finance.reports.ap_aging', only: ['index', 'detail', 'recalculate', 'export']),
        ];
    }

    public function __construct(APAgingService $apAgingService)
    {
        $this->apAgingService = $apAgingService;
    }

    /**
     * Display AP aging report
     */
    public function index(Request $request)
    {
        $filters = [
            'as_of_date' => $request->get('as_of_date', now()->format('Y-m-d')),
            'currency' => $request->get('currency', 'IDR'),
            'supplier_id' => $request->get('supplier_id'),
        ];

        $agingReport = $this->apAgingService->getAgingReport($filters);

        // Transform for frontend
        $suppliers = collect($agingReport['suppliers'])->map(function ($supplier) {
            return [
                'supplier_id' => $supplier['supplier_id'],
                'supplier_name' => $supplier['supplier_name'],
                'email' => $supplier['email'],
                'current_0_30' => $supplier['current_0_30'],
                'current_31_60' => $supplier['current_31_60'],
                'current_61_90' => $supplier['current_61_90'],
                'current_over_90' => $supplier['current_over_90'],
                'total_balance' => $supplier['total_balance'],
                'total_overdue' => $supplier['current_31_60'] + $supplier['current_61_90'] + $supplier['current_over_90'],
                'aging_status' => $this->getAgingStatus($supplier),
                'order_count' => count($supplier['orders']),
            ];
        });

        return Inertia::render('Finance/Reports/APAgingReport', [
            'suppliers' => $suppliers,
            'totals' => $agingReport['totals'],
            'summary' => $agingReport['summary'],
            'filters' => $filters,
        ]);
    }

    /**
     * Get AP aging detail for a specific supplier (API)
     */
    public function detail(Request $request, int $supplierId)
    {
        $filters = [
            'as_of_date' => $request->get('as_of_date', now()->format('Y-m-d')),
            'currency' => $request->get('currency', 'IDR'),
            'supplier_id' => $supplierId,
        ];

        $agingReport = $this->apAgingService->getAgingReport($filters);
        $supplierData = $agingReport['suppliers'][0] ?? null;

        if (!$supplierData) {
            return response()->json([
                'success' => false,
                'message' => 'No outstanding balance for this supplier.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $supplierData,
        ]);
    }

    /**
     * Recalculate all AP balances (API)
     */
    public function recalculate(Request $request)
    {
        $currency = $request->get('currency', 'IDR');
        $updatedCount = $this->apAgingService->recalculateAllBalances($currency);

        return response()->json([
            'success' => true,
            'message' => "Recalculated {$updatedCount} supplier balances.",
            'updated_count' => $updatedCount,
        ]);
    }

    /**
     * Export AP aging to Excel/PDF (future)
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
    protected function getAgingStatus(array $supplier): string
    {
        if ($supplier['current_over_90'] > 0) {
            return 'critical';
        } elseif ($supplier['current_61_90'] > 0) {
            return 'warning';
        } elseif ($supplier['current_31_60'] > 0) {
            return 'attention';
        } elseif ($supplier['current_0_30'] > 0) {
            return 'current';
        }

        return 'none';
    }
}

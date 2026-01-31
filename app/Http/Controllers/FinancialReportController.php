<?php

namespace App\Http\Controllers;

use App\Models\FiscalPeriod;
use App\Models\FiscalYear;
use App\Services\FinancialReportService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class FinancialReportController extends Controller implements HasMiddleware
{
    protected FinancialReportService $reportService;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:finance.reports.trial_balance', only: ['trialBalance']),
            new Middleware('permission:finance.reports.profit_loss', only: ['profitLoss']),
            new Middleware('permission:finance.reports.balance_sheet', only: ['balanceSheet']),
        ];
    }

    public function __construct(FinancialReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Trial Balance Report
     */
    public function trialBalance(Request $request)
    {
        $periodId = $request->get('period_id');
        $asOfDate = $request->get('as_of_date', now()->format('Y-m-d'));

        $report = $this->reportService->getTrialBalance($periodId, $asOfDate);

        // Get fiscal periods for dropdown
        $fiscalPeriods = FiscalPeriod::with('fiscalYear')
            ->orderBy('start_date', 'desc')
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->fiscalYear->name . ' - ' . $p->name,
                'end_date' => $p->end_date->format('Y-m-d'),
            ]);

        return Inertia::render('Finance/Reports/TrialBalance', [
            'report' => $report,
            'fiscalPeriods' => $fiscalPeriods,
            'filters' => [
                'period_id' => $periodId,
                'as_of_date' => $asOfDate,
            ],
        ]);
    }

    /**
     * Profit & Loss Report
     */
    public function profitLoss(Request $request)
    {
        // Default to current month
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $compareMode = $request->get('compare_mode'); // previous_period, previous_year

        $report = $this->reportService->getProfitAndLoss($startDate, $endDate);

        // Get comparison data if requested
        $comparison = null;
        if ($compareMode === 'previous_period') {
            $start = \Carbon\Carbon::parse($startDate);
            $end = \Carbon\Carbon::parse($endDate);
            $duration = $start->diffInDays($end);

            $previousEnd = $start->copy()->subDay();
            $previousStart = $previousEnd->copy()->subDays($duration);

            $comparison = $this->reportService->getProfitAndLoss(
                $previousStart->format('Y-m-d'),
                $previousEnd->format('Y-m-d')
            );
        } elseif ($compareMode === 'previous_year') {
            $previousStart = \Carbon\Carbon::parse($startDate)->subYear();
            $previousEnd = \Carbon\Carbon::parse($endDate)->subYear();

            $comparison = $this->reportService->getProfitAndLoss(
                $previousStart->format('Y-m-d'),
                $previousEnd->format('Y-m-d')
            );
        }

        // Get fiscal years for quick select
        $fiscalYears = FiscalYear::orderBy('start_date', 'desc')
            ->get()
            ->map(fn($y) => [
                'id' => $y->id,
                'name' => $y->name,
                'start_date' => $y->start_date->format('Y-m-d'),
                'end_date' => $y->end_date->format('Y-m-d'),
            ]);

        return Inertia::render('Finance/Reports/ProfitLoss', [
            'report' => $report,
            'comparison' => $comparison,
            'fiscalYears' => $fiscalYears,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'compare_mode' => $compareMode,
            ],
        ]);
    }

    /**
     * Balance Sheet Report
     */
    public function balanceSheet(Request $request)
    {
        $asOfDate = $request->get('as_of_date', now()->format('Y-m-d'));
        $compareDate = $request->get('compare_date'); // For comparison

        $report = $this->reportService->getBalanceSheet($asOfDate);

        // Get comparison if requested
        $comparison = null;
        if ($compareDate) {
            $comparison = $this->reportService->getBalanceSheet($compareDate);
        }

        return Inertia::render('Finance/Reports/BalanceSheet', [
            'report' => $report,
            'comparison' => $comparison,
            'filters' => [
                'as_of_date' => $asOfDate,
                'compare_date' => $compareDate,
            ],
        ]);
    }

    /**
     * Get Trial Balance data (API)
     */
    public function getTrialBalance(Request $request)
    {
        $periodId = $request->get('period_id');
        $asOfDate = $request->get('as_of_date', now()->format('Y-m-d'));

        $report = $this->reportService->getTrialBalance($periodId, $asOfDate);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get P&L data (API)
     */
    public function getProfitLoss(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $report = $this->reportService->getProfitAndLoss($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get Balance Sheet data (API)
     */
    public function getBalanceSheet(Request $request)
    {
        $asOfDate = $request->get('as_of_date', now()->format('Y-m-d'));

        $report = $this->reportService->getBalanceSheet($asOfDate);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }
}

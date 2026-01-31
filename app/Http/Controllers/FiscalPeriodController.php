<?php

namespace App\Http\Controllers;

use App\Models\FiscalPeriod;
use App\Models\FiscalYear;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class FiscalPeriodController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:finance.fiscal_periods.view', only: ['index', 'getOpenPeriods']),
            new Middleware('permission:finance.fiscal_periods.manage', only: ['createYear', 'openPeriod', 'closePeriod', 'reopenPeriod']),
        ];
    }
    /**
     * Display fiscal years and periods.
     */
    public function index(Request $request)
    {
        $fiscalYears = FiscalYear::with(['periods' => function ($query) {
            $query->orderBy('period_number');
        }])
            ->orderByDesc('start_date')
            ->get()
            ->map(function ($year) {
                return [
                    'id' => $year->id,
                    'name' => $year->name,
                    'start_date' => $year->start_date->format('Y-m-d'),
                    'end_date' => $year->end_date->format('Y-m-d'),
                    'date_range' => $year->date_range,
                    'status' => $year->status,
                    'status_label' => $year->status_label,
                    'status_color' => $year->status_color,
                    'is_current' => $year->is_current,
                    'periods' => $year->periods->map(function ($period) {
                        return [
                            'id' => $period->id,
                            'name' => $period->name,
                            'period_number' => $period->period_number,
                            'start_date' => $period->start_date->format('Y-m-d'),
                            'end_date' => $period->end_date->format('Y-m-d'),
                            'date_range' => $period->date_range,
                            'status' => $period->status,
                            'status_label' => $period->status_label,
                            'status_color' => $period->status_color,
                            'is_postable' => $period->is_postable,
                            'is_adjusting_period' => $period->is_adjusting_period,
                        ];
                    }),
                ];
            });

        // Get current period info
        $currentPeriod = FiscalPeriod::getCurrent();
        $currentPeriodInfo = $currentPeriod ? [
            'id' => $currentPeriod->id,
            'name' => $currentPeriod->name,
            'fiscal_year' => $currentPeriod->fiscalYear->name,
            'date_range' => $currentPeriod->date_range,
            'status' => $currentPeriod->status,
        ] : null;

        return Inertia::render('Finance/FiscalPeriods/Index', [
            'fiscalYears' => $fiscalYears,
            'currentPeriod' => $currentPeriodInfo,
        ]);
    }

    /**
     * Create a new fiscal year.
     */
    public function createYear(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'is_current' => ['boolean'],
        ]);

        // Check for overlapping fiscal years
        $overlapping = FiscalYear::where(function ($query) use ($validated) {
            $query->whereBetween('start_date', [$validated['start_date'], $validated['end_date']])
                ->orWhereBetween('end_date', [$validated['start_date'], $validated['end_date']])
                ->orWhere(function ($q) use ($validated) {
                    $q->where('start_date', '<=', $validated['start_date'])
                        ->where('end_date', '>=', $validated['end_date']);
                });
        })->exists();

        if ($overlapping) {
            return response()->json([
                'success' => false,
                'message' => 'The date range overlaps with an existing fiscal year.',
            ], 422);
        }

        $fiscalYear = FiscalYear::create([
            'name' => $validated['name'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => 'open',
            'is_current' => false,
            'created_by' => Auth::id(),
        ]);

        // Create monthly periods
        $fiscalYear->createPeriods();

        // Set as current if requested
        if ($validated['is_current'] ?? false) {
            $fiscalYear->setAsCurrent();
        }

        return response()->json([
            'success' => true,
            'message' => 'Fiscal year created successfully.',
            'fiscal_year' => $fiscalYear->load('periods'),
        ]);
    }

    /**
     * Close a fiscal period.
     */
    public function closePeriod(FiscalPeriod $period)
    {
        if (!$period->canClose()) {
            return response()->json([
                'success' => false,
                'message' => 'This period cannot be closed. Previous periods must be closed first.',
            ], 422);
        }

        $period->close(Auth::id());

        return response()->json([
            'success' => true,
            'message' => 'Period closed successfully.',
            'period' => $period->fresh(),
        ]);
    }

    /**
     * Reopen a closed period.
     */
    public function reopenPeriod(FiscalPeriod $period)
    {
        if (!$period->reopen()) {
            return response()->json([
                'success' => false,
                'message' => 'This period cannot be reopened. Later periods must be open first.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Period reopened successfully.',
            'period' => $period->fresh(),
        ]);
    }

    /**
     * Close a fiscal year.
     */
    public function closeYear(FiscalYear $year)
    {
        if (!$year->canClose()) {
            return response()->json([
                'success' => false,
                'message' => 'All periods must be closed before closing the fiscal year.',
            ], 422);
        }

        $year->close(Auth::id());

        return response()->json([
            'success' => true,
            'message' => 'Fiscal year closed successfully.',
            'year' => $year->fresh()->load('periods'),
        ]);
    }

    /**
     * Set a fiscal year as current.
     */
    public function setCurrentYear(FiscalYear $year)
    {
        $year->setAsCurrent();

        return response()->json([
            'success' => true,
            'message' => 'Fiscal year set as current.',
        ]);
    }

    /**
     * Get period by date (API).
     */
    public function getByDate(Request $request)
    {
        $date = $request->filled('date')
            ? Carbon::parse($request->date)
            : Carbon::today();

        $period = FiscalPeriod::getByDate($date);

        if (!$period) {
            return response()->json([
                'success' => false,
                'message' => 'No fiscal period found for the specified date.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'period' => [
                'id' => $period->id,
                'name' => $period->name,
                'fiscal_year' => $period->fiscalYear->name,
                'is_postable' => $period->is_postable,
            ],
        ]);
    }
}

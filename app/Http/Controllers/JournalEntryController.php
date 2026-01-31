<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class JournalEntryController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:finance.journal_entries.view', only: ['index', 'show']),
            new Middleware('permission:finance.journal_entries.create', only: ['create', 'store']),
            new Middleware('permission:finance.journal_entries.edit', only: ['edit', 'update']),
            new Middleware('permission:finance.journal_entries.delete', only: ['destroy']),
            new Middleware('permission:finance.journal_entries.post', only: ['post']),
            new Middleware('permission:finance.journal_entries.void', only: ['void', 'reverse']),
        ];
    }

    /**
     * Display list of journal entries.
     */
    public function index(Request $request)
    {
        $query = JournalEntry::with(['fiscalPeriod', 'creator', 'poster']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('entry_number', 'like', "%{$search}%")
                    ->orWhere('memo', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Entry type filter
        if ($request->filled('entry_type')) {
            $query->where('entry_type', $request->entry_type);
        }

        // Fiscal period filter
        if ($request->filled('fiscal_period_id')) {
            $query->where('fiscal_period_id', $request->fiscal_period_id);
        }

        // Date range filter
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->dateRange($request->start_date, $request->end_date);
        }

        // Sort
        $sortField = $request->get('sort', 'entry_date');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Paginate
        $perPage = $request->get('per_page', 15);
        $entries = $query->paginate($perPage);

        // Transform for frontend
        $entries->getCollection()->transform(function ($entry) {
            return [
                'id' => $entry->id,
                'entry_number' => $entry->entry_number,
                'entry_date' => $entry->entry_date->format('Y-m-d'),
                'entry_date_formatted' => $entry->entry_date->format('d M Y'),
                'entry_type' => $entry->entry_type,
                'entry_type_label' => $entry->entry_type_label,
                'memo' => $entry->memo,
                'total_debit' => $entry->total_debit,
                'total_credit' => $entry->total_credit,
                'currency_code' => $entry->currency_code,
                'status' => $entry->status,
                'status_label' => $entry->status_label,
                'status_color' => $entry->status_color,
                'is_balanced' => $entry->is_balanced,
                'fiscal_period' => $entry->fiscalPeriod ? [
                    'id' => $entry->fiscalPeriod->id,
                    'name' => $entry->fiscalPeriod->name,
                ] : null,
                'creator' => $entry->creator ? [
                    'id' => $entry->creator->id,
                    'name' => $entry->creator->name,
                ] : null,
                'posted_at' => $entry->posted_at?->format('d M Y H:i'),
                'can_edit' => $entry->canEdit(),
                'can_post' => $entry->canPost(),
                'can_void' => $entry->canVoid(),
                'can_delete' => $entry->canDelete(),
            ];
        });

        // Get fiscal periods for filter
        $fiscalPeriods = FiscalPeriod::with('fiscalYear')
            ->orderBy('start_date', 'desc')
            ->get()
            ->map(fn($p) => [
                'value' => $p->id,
                'label' => $p->fiscalYear->name . ' - ' . $p->name,
            ]);

        return Inertia::render('Finance/JournalEntries/Index', [
            'entries' => $entries,
            'fiscalPeriods' => $fiscalPeriods,
            'entryTypes' => JournalEntry::ENTRY_TYPES,
            'filters' => $request->only(['search', 'status', 'entry_type', 'fiscal_period_id', 'start_date', 'end_date']),
            'stats' => [
                'total' => JournalEntry::count(),
                'draft' => JournalEntry::draft()->count(),
                'posted' => JournalEntry::posted()->count(),
                'voided' => JournalEntry::voided()->count(),
                'total_debit' => JournalEntry::posted()->sum('total_debit'),
                'total_credit' => JournalEntry::posted()->sum('total_credit'),
            ],
        ]);
    }

    /**
     * Show create journal entry form.
     */
    public function create()
    {
        // Get postable accounts
        $accounts = ChartOfAccount::active()
            ->postable()
            ->orderBy('account_code')
            ->get()
            ->map(fn($a) => [
                'value' => $a->id,
                'label' => $a->account_code . ' - ' . $a->account_name,
                'account_code' => $a->account_code,
                'account_name' => $a->account_name,
                'account_type' => $a->account_type,
                'normal_balance' => $a->normal_balance,
            ]);

        // Get current fiscal period
        $currentPeriod = FiscalPeriod::getCurrentPeriod();

        // Get available fiscal periods
        $fiscalPeriods = FiscalPeriod::with('fiscalYear')
            ->whereIn('status', ['open', 'adjusting'])
            ->orderBy('start_date', 'desc')
            ->get()
            ->map(fn($p) => [
                'value' => $p->id,
                'label' => $p->fiscalYear->name . ' - ' . $p->name,
                'start_date' => $p->start_date->format('Y-m-d'),
                'end_date' => $p->end_date->format('Y-m-d'),
            ]);

        return Inertia::render('Finance/JournalEntries/Create', [
            'accounts' => $accounts,
            'fiscalPeriods' => $fiscalPeriods,
            'currentPeriodId' => $currentPeriod?->id,
            'nextNumber' => JournalEntry::generateEntryNumber(),
        ]);
    }

    /**
     * Store new journal entry.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'entry_date' => ['required', 'date'],
            'fiscal_period_id' => ['required', 'exists:fin_fiscal_periods,id'],
            'memo' => ['nullable', 'string', 'max:255'],
            'currency_code' => ['required', 'string', 'max:3'],
            'exchange_rate' => ['required', 'numeric', 'min:0'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'exists:fin_chart_of_accounts,id'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.debit_amount' => ['required', 'numeric', 'min:0'],
            'lines.*.credit_amount' => ['required', 'numeric', 'min:0'],
        ]);

        // Validate entry is balanced
        $totalDebit = collect($validated['lines'])->sum('debit_amount');
        $totalCredit = collect($validated['lines'])->sum('credit_amount');

        if (bccomp($totalDebit, $totalCredit, 2) !== 0) {
            return response()->json([
                'success' => false,
                'message' => 'Journal entry must be balanced. Debit and credit totals must be equal.',
            ], 422);
        }

        // Validate fiscal period is open
        $period = FiscalPeriod::find($validated['fiscal_period_id']);
        if (!$period || !in_array($period->status, ['open', 'adjusting'])) {
            return response()->json([
                'success' => false,
                'message' => 'Selected fiscal period is not open for posting.',
            ], 422);
        }

        // Validate entry date is within period
        $entryDate = \Carbon\Carbon::parse($validated['entry_date']);
        if ($entryDate->lt($period->start_date) || $entryDate->gt($period->end_date)) {
            return response()->json([
                'success' => false,
                'message' => 'Entry date must be within the selected fiscal period.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            $exchangeRate = $validated['exchange_rate'];

            $entry = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber(),
                'entry_date' => $validated['entry_date'],
                'fiscal_period_id' => $validated['fiscal_period_id'],
                'entry_type' => 'manual',
                'memo' => $validated['memo'] ?? null,
                'currency_code' => $validated['currency_code'],
                'exchange_rate' => $exchangeRate,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'status' => 'draft',
                'created_by' => Auth::id(),
            ]);

            // Create lines
            $lineNumber = 1;
            foreach ($validated['lines'] as $lineData) {
                // Skip empty lines
                if ($lineData['debit_amount'] == 0 && $lineData['credit_amount'] == 0) {
                    continue;
                }

                $line = new JournalEntryLine([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $lineData['account_id'],
                    'line_number' => $lineNumber++,
                    'description' => $lineData['description'] ?? null,
                    'debit_amount' => $lineData['debit_amount'],
                    'credit_amount' => $lineData['credit_amount'],
                ]);

                // Calculate base currency amounts
                $line->calculateBaseAmounts($exchangeRate);
                $line->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Journal entry created successfully.',
                'entry' => $entry->load('lines.account'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create journal entry: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show journal entry detail.
     */
    public function show(JournalEntry $entry)
    {
        $entry->load([
            'lines.account',
            'fiscalPeriod.fiscalYear',
            'creator',
            'poster',
            'voider',
            'reversedBy',
            'reverses',
        ]);

        return Inertia::render('Finance/JournalEntries/Detail', [
            'entry' => [
                'id' => $entry->id,
                'entry_number' => $entry->entry_number,
                'entry_date' => $entry->entry_date->format('Y-m-d'),
                'entry_date_formatted' => $entry->entry_date->format('d M Y'),
                'entry_type' => $entry->entry_type,
                'entry_type_label' => $entry->entry_type_label,
                'reference_type' => $entry->reference_type,
                'reference_id' => $entry->reference_id,
                'memo' => $entry->memo,
                'currency_code' => $entry->currency_code,
                'exchange_rate' => $entry->exchange_rate,
                'total_debit' => $entry->total_debit,
                'total_credit' => $entry->total_credit,
                'status' => $entry->status,
                'status_label' => $entry->status_label,
                'status_color' => $entry->status_color,
                'is_balanced' => $entry->is_balanced,
                'void_reason' => $entry->void_reason,
                'fiscal_period' => $entry->fiscalPeriod ? [
                    'id' => $entry->fiscalPeriod->id,
                    'name' => $entry->fiscalPeriod->name,
                    'fiscal_year' => $entry->fiscalPeriod->fiscalYear->name,
                ] : null,
                'creator' => $entry->creator ? [
                    'id' => $entry->creator->id,
                    'name' => $entry->creator->name,
                ] : null,
                'poster' => $entry->poster ? [
                    'id' => $entry->poster->id,
                    'name' => $entry->poster->name,
                ] : null,
                'voider' => $entry->voider ? [
                    'id' => $entry->voider->id,
                    'name' => $entry->voider->name,
                ] : null,
                'created_at' => $entry->created_at->format('d M Y H:i'),
                'posted_at' => $entry->posted_at?->format('d M Y H:i'),
                'voided_at' => $entry->voided_at?->format('d M Y H:i'),
                'reversed_by' => $entry->reversedBy ? [
                    'id' => $entry->reversedBy->id,
                    'entry_number' => $entry->reversedBy->entry_number,
                ] : null,
                'reverses' => $entry->reverses ? [
                    'id' => $entry->reverses->id,
                    'entry_number' => $entry->reverses->entry_number,
                ] : null,
                'lines' => $entry->lines->map(function ($line) {
                    return [
                        'id' => $line->id,
                        'line_number' => $line->line_number,
                        'account' => [
                            'id' => $line->account->id,
                            'code' => $line->account->account_code,
                            'name' => $line->account->account_name,
                            'type' => $line->account->account_type,
                        ],
                        'description' => $line->description,
                        'debit_amount' => $line->debit_amount,
                        'credit_amount' => $line->credit_amount,
                    ];
                }),
                'can_edit' => $entry->canEdit(),
                'can_post' => $entry->canPost(),
                'can_void' => $entry->canVoid(),
                'can_delete' => $entry->canDelete(),
            ],
        ]);
    }

    /**
     * Show edit form.
     */
    public function edit(JournalEntry $entry)
    {
        if (!$entry->canEdit()) {
            return redirect()->route('finance.journal-entries.show', $entry->id)
                ->with('error', 'This journal entry cannot be edited.');
        }

        $entry->load(['lines.account', 'fiscalPeriod']);

        // Get postable accounts
        $accounts = ChartOfAccount::active()
            ->postable()
            ->orderBy('account_code')
            ->get()
            ->map(fn($a) => [
                'value' => $a->id,
                'label' => $a->account_code . ' - ' . $a->account_name,
                'account_code' => $a->account_code,
                'account_name' => $a->account_name,
                'account_type' => $a->account_type,
                'normal_balance' => $a->normal_balance,
            ]);

        // Get available fiscal periods
        $fiscalPeriods = FiscalPeriod::with('fiscalYear')
            ->whereIn('status', ['open', 'adjusting'])
            ->orderBy('start_date', 'desc')
            ->get()
            ->map(fn($p) => [
                'value' => $p->id,
                'label' => $p->fiscalYear->name . ' - ' . $p->name,
                'start_date' => $p->start_date->format('Y-m-d'),
                'end_date' => $p->end_date->format('Y-m-d'),
            ]);

        return Inertia::render('Finance/JournalEntries/Edit', [
            'entry' => [
                'id' => $entry->id,
                'entry_number' => $entry->entry_number,
                'entry_date' => $entry->entry_date->format('Y-m-d'),
                'fiscal_period_id' => $entry->fiscal_period_id,
                'memo' => $entry->memo,
                'currency_code' => $entry->currency_code,
                'exchange_rate' => $entry->exchange_rate,
                'lines' => $entry->lines->map(function ($line) {
                    return [
                        'id' => $line->id,
                        'account_id' => $line->account_id,
                        'description' => $line->description,
                        'debit_amount' => $line->debit_amount,
                        'credit_amount' => $line->credit_amount,
                    ];
                }),
            ],
            'accounts' => $accounts,
            'fiscalPeriods' => $fiscalPeriods,
        ]);
    }

    /**
     * Update journal entry.
     */
    public function update(Request $request, JournalEntry $entry)
    {
        if (!$entry->canEdit()) {
            return response()->json([
                'success' => false,
                'message' => 'This journal entry cannot be edited.',
            ], 422);
        }

        $validated = $request->validate([
            'entry_date' => ['required', 'date'],
            'fiscal_period_id' => ['required', 'exists:fin_fiscal_periods,id'],
            'memo' => ['nullable', 'string', 'max:255'],
            'currency_code' => ['required', 'string', 'max:3'],
            'exchange_rate' => ['required', 'numeric', 'min:0'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'exists:fin_chart_of_accounts,id'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.debit_amount' => ['required', 'numeric', 'min:0'],
            'lines.*.credit_amount' => ['required', 'numeric', 'min:0'],
        ]);

        // Validate entry is balanced
        $totalDebit = collect($validated['lines'])->sum('debit_amount');
        $totalCredit = collect($validated['lines'])->sum('credit_amount');

        if (bccomp($totalDebit, $totalCredit, 2) !== 0) {
            return response()->json([
                'success' => false,
                'message' => 'Journal entry must be balanced. Debit and credit totals must be equal.',
            ], 422);
        }

        // Validate fiscal period is open
        $period = FiscalPeriod::find($validated['fiscal_period_id']);
        if (!$period || !in_array($period->status, ['open', 'adjusting'])) {
            return response()->json([
                'success' => false,
                'message' => 'Selected fiscal period is not open for posting.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            $exchangeRate = $validated['exchange_rate'];

            $entry->update([
                'entry_date' => $validated['entry_date'],
                'fiscal_period_id' => $validated['fiscal_period_id'],
                'memo' => $validated['memo'] ?? null,
                'currency_code' => $validated['currency_code'],
                'exchange_rate' => $exchangeRate,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
            ]);

            // Delete existing lines
            $entry->lines()->delete();

            // Create new lines
            $lineNumber = 1;
            foreach ($validated['lines'] as $lineData) {
                if ($lineData['debit_amount'] == 0 && $lineData['credit_amount'] == 0) {
                    continue;
                }

                $line = new JournalEntryLine([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $lineData['account_id'],
                    'line_number' => $lineNumber++,
                    'description' => $lineData['description'] ?? null,
                    'debit_amount' => $lineData['debit_amount'],
                    'credit_amount' => $lineData['credit_amount'],
                ]);

                $line->calculateBaseAmounts($exchangeRate);
                $line->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Journal entry updated successfully.',
                'entry' => $entry->fresh()->load('lines.account'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update journal entry: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete journal entry.
     */
    public function destroy(JournalEntry $entry)
    {
        if (!$entry->canDelete()) {
            return response()->json([
                'success' => false,
                'message' => 'This journal entry cannot be deleted.',
            ], 422);
        }

        $entry->lines()->delete();
        $entry->delete();

        return response()->json([
            'success' => true,
            'message' => 'Journal entry deleted successfully.',
        ]);
    }

    /**
     * Post journal entry.
     */
    public function post(JournalEntry $entry)
    {
        if (!$entry->canPost()) {
            return response()->json([
                'success' => false,
                'message' => 'This journal entry cannot be posted. Ensure it is balanced and has at least 2 lines.',
            ], 422);
        }

        if ($entry->post(Auth::id())) {
            return response()->json([
                'success' => true,
                'message' => 'Journal entry posted successfully.',
                'entry' => $entry->fresh(),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to post journal entry.',
        ], 500);
    }

    /**
     * Void journal entry.
     */
    public function void(Request $request, JournalEntry $entry)
    {
        if (!$entry->canVoid()) {
            return response()->json([
                'success' => false,
                'message' => 'This journal entry cannot be voided.',
            ], 422);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        if ($entry->void(Auth::id(), $validated['reason'])) {
            return response()->json([
                'success' => true,
                'message' => 'Journal entry voided successfully.',
                'entry' => $entry->fresh(),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to void journal entry.',
        ], 500);
    }

    /**
     * Create reversing entry.
     */
    public function reverse(JournalEntry $entry)
    {
        if (!$entry->canVoid()) {
            return response()->json([
                'success' => false,
                'message' => 'This journal entry cannot be reversed.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Create reversing entry
            $reversingEntry = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber(),
                'entry_date' => now()->format('Y-m-d'),
                'fiscal_period_id' => $entry->fiscal_period_id,
                'entry_type' => 'adjustment',
                'reference_type' => 'journal_entry',
                'reference_id' => $entry->id,
                'memo' => 'Reversal of ' . $entry->entry_number,
                'currency_code' => $entry->currency_code,
                'exchange_rate' => $entry->exchange_rate,
                'total_debit' => $entry->total_credit,
                'total_credit' => $entry->total_debit,
                'status' => 'draft',
                'reverses_id' => $entry->id,
                'created_by' => Auth::id(),
            ]);

            // Create reversed lines (swap debit/credit)
            $lineNumber = 1;
            foreach ($entry->lines as $originalLine) {
                JournalEntryLine::create([
                    'journal_entry_id' => $reversingEntry->id,
                    'account_id' => $originalLine->account_id,
                    'line_number' => $lineNumber++,
                    'description' => 'Reversal: ' . ($originalLine->description ?? ''),
                    'debit_amount' => $originalLine->credit_amount,
                    'credit_amount' => $originalLine->debit_amount,
                    'debit_amount_base' => $originalLine->credit_amount_base,
                    'credit_amount_base' => $originalLine->debit_amount_base,
                ]);
            }

            // Mark original as having a reversal
            $entry->update(['reversed_by_id' => $reversingEntry->id]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Reversing entry created successfully.',
                'entry' => $reversingEntry->load('lines.account'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create reversing entry: ' . $e->getMessage(),
            ], 500);
        }
    }
}

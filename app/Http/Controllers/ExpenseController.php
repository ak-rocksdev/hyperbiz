<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseAttachment;
use App\Models\ExpenseCategory;
use App\Services\ExpenseJournalService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ExpenseController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:finance.expenses.view', only: ['index', 'show']),
            new Middleware('permission:finance.expenses.create', only: ['create', 'store']),
            new Middleware('permission:finance.expenses.edit', only: ['edit', 'update']),
            new Middleware('permission:finance.expenses.delete', only: ['destroy']),
            new Middleware('permission:finance.expenses.approve', only: ['approve', 'post', 'cancel']),
        ];
    }
    /**
     * Display list of expenses.
     */
    public function index(Request $request)
    {
        $query = Expense::with(['category', 'account', 'supplier', 'creator', 'approver']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('expense_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('payee_name', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Payment status filter
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Category filter
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Date range filter
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->dateBetween($request->start_date, $request->end_date);
        }

        // Sort
        $sortField = $request->get('sort', 'expense_date');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Paginate
        $perPage = $request->get('per_page', 15);
        $expenses = $query->paginate($perPage);

        // Transform for frontend
        $expenses->getCollection()->transform(function ($expense) {
            return [
                'id' => $expense->id,
                'expense_number' => $expense->expense_number,
                'expense_date' => $expense->expense_date->format('Y-m-d'),
                'expense_date_formatted' => $expense->expense_date->format('d M Y'),
                'category' => $expense->category ? [
                    'id' => $expense->category->id,
                    'code' => $expense->category->code,
                    'name' => $expense->category->name,
                ] : null,
                'account' => $expense->account ? [
                    'id' => $expense->account->id,
                    'code' => $expense->account->account_code,
                    'name' => $expense->account->account_name,
                ] : null,
                'payee' => $expense->supplier?->name ?? $expense->payee_name,
                'description' => $expense->description,
                'amount' => $expense->amount,
                'tax_amount' => $expense->tax_amount,
                'total_amount' => $expense->total_amount,
                'currency_code' => $expense->currency_code,
                'status' => $expense->status,
                'status_label' => $expense->status_label,
                'status_color' => $expense->status_color,
                'payment_status' => $expense->payment_status,
                'payment_status_label' => $expense->payment_status_label,
                'payment_status_color' => $expense->payment_status_color,
                'can_edit' => $expense->canEdit(),
                'can_delete' => $expense->canDelete(),
                'can_approve' => $expense->canApprove(),
                'can_post' => $expense->canPost(),
            ];
        });

        // Get categories for filter
        $categories = ExpenseCategory::active()
            ->orderBy('code')
            ->get()
            ->map(fn($c) => ['value' => $c->id, 'label' => $c->code . ' - ' . $c->name]);

        return Inertia::render('Finance/Expenses/Index', [
            'expenses' => $expenses,
            'categories' => $categories,
            'filters' => $request->only(['search', 'status', 'payment_status', 'category_id', 'start_date', 'end_date']),
            'stats' => [
                'total' => Expense::count(),
                'draft' => Expense::draft()->count(),
                'approved' => Expense::approved()->count(),
                'posted' => Expense::posted()->count(),
                'total_amount' => Expense::posted()->sum('total_amount'),
                'unpaid_amount' => Expense::whereIn('payment_status', ['unpaid', 'partial'])->sum(DB::raw('total_amount - amount_paid')),
            ],
        ]);
    }

    /**
     * Show create expense form.
     */
    public function create()
    {
        $categories = ExpenseCategory::getForSelect();

        $accounts = ChartOfAccount::active()
            ->postable()
            ->where('account_type', 'expense')
            ->orderBy('account_code')
            ->get()
            ->map(fn($a) => ['value' => $a->id, 'label' => $a->account_code . ' - ' . $a->account_name]);

        $paymentAccounts = ChartOfAccount::active()
            ->postable()
            ->whereIn('account_type', ['asset'])
            ->where(function ($q) {
                $q->where('is_bank_account', true)
                    ->orWhere('account_code', 'like', '111%'); // Cash accounts
            })
            ->orderBy('account_code')
            ->get()
            ->map(fn($a) => ['value' => $a->id, 'label' => $a->account_code . ' - ' . $a->account_name]);

        $suppliers = Customer::where('customer_type', 'supplier')
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(fn($s) => ['value' => $s->id, 'label' => $s->name]);

        return Inertia::render('Finance/Expenses/Create', [
            'categories' => $categories,
            'accounts' => $accounts,
            'paymentAccounts' => $paymentAccounts,
            'suppliers' => $suppliers,
            'nextNumber' => Expense::generateNumber(),
        ]);
    }

    /**
     * Store new expense.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'expense_date' => ['required', 'date'],
            'category_id' => ['required', 'exists:mst_expense_categories,id'],
            'account_id' => ['required', 'exists:fin_chart_of_accounts,id'],
            'paid_from_account_id' => ['nullable', 'exists:fin_chart_of_accounts,id'],
            'supplier_id' => ['nullable', 'exists:mst_customers,id'],
            'payee_name' => ['nullable', 'string', 'max:100'],
            'currency_code' => ['required', 'string', 'max:3'],
            'exchange_rate' => ['required', 'numeric', 'min:0'],
            'amount' => ['required', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'in:cash,bank_transfer,credit_card,check,other'],
            'reference_number' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'is_recurring' => ['boolean'],
            'recurring_frequency' => ['nullable', 'in:daily,weekly,monthly,quarterly,yearly'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240'], // 10MB max
        ]);

        DB::beginTransaction();

        try {
            // Calculate amounts
            $amount = $validated['amount'];
            $taxAmount = $validated['tax_amount'] ?? 0;
            $exchangeRate = $validated['exchange_rate'];

            $expense = Expense::create([
                'expense_number' => Expense::generateNumber(),
                'expense_date' => $validated['expense_date'],
                'category_id' => $validated['category_id'],
                'account_id' => $validated['account_id'],
                'paid_from_account_id' => $validated['paid_from_account_id'] ?? null,
                'supplier_id' => $validated['supplier_id'] ?? null,
                'payee_name' => $validated['payee_name'] ?? null,
                'currency_code' => $validated['currency_code'],
                'exchange_rate' => $exchangeRate,
                'amount' => $amount,
                'amount_in_base' => bcmul((string) $amount, (string) $exchangeRate, 2),
                'tax_amount' => $taxAmount,
                'total_amount' => bcadd((string) $amount, (string) $taxAmount, 2),
                'payment_method' => $validated['payment_method'] ?? null,
                'reference_number' => $validated['reference_number'] ?? null,
                'description' => $validated['description'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'is_recurring' => $validated['is_recurring'] ?? false,
                'recurring_frequency' => $validated['recurring_frequency'] ?? null,
                'status' => Expense::STATUS_DRAFT,
                'payment_status' => Expense::PAYMENT_UNPAID,
                'created_by' => Auth::id(),
            ]);

            // Handle attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('expenses/' . $expense->id, 'public');

                    ExpenseAttachment::create([
                        'expense_id' => $expense->id,
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                        'uploaded_by' => Auth::id(),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Expense created successfully.',
                'expense' => $expense->load(['category', 'account', 'attachments']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create expense: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show expense detail.
     */
    public function show(Expense $expense)
    {
        $expense->load(['category', 'account', 'paidFromAccount', 'supplier', 'attachments', 'creator', 'approver', 'journalEntry']);

        return Inertia::render('Finance/Expenses/Detail', [
            'expense' => [
                'id' => $expense->id,
                'expense_number' => $expense->expense_number,
                'expense_date' => $expense->expense_date->format('Y-m-d'),
                'expense_date_formatted' => $expense->expense_date->format('d M Y'),
                'category' => $expense->category ? [
                    'id' => $expense->category->id,
                    'code' => $expense->category->code,
                    'name' => $expense->category->name,
                ] : null,
                'account' => $expense->account ? [
                    'id' => $expense->account->id,
                    'code' => $expense->account->account_code,
                    'name' => $expense->account->account_name,
                ] : null,
                'paid_from_account' => $expense->paidFromAccount ? [
                    'id' => $expense->paidFromAccount->id,
                    'code' => $expense->paidFromAccount->account_code,
                    'name' => $expense->paidFromAccount->account_name,
                ] : null,
                'supplier' => $expense->supplier ? [
                    'id' => $expense->supplier->id,
                    'name' => $expense->supplier->name,
                ] : null,
                'payee_name' => $expense->payee_name,
                'payee' => $expense->supplier?->name ?? $expense->payee_name,
                'currency_code' => $expense->currency_code,
                'exchange_rate' => $expense->exchange_rate,
                'amount' => $expense->amount,
                'amount_in_base' => $expense->amount_in_base,
                'tax_amount' => $expense->tax_amount,
                'total_amount' => $expense->total_amount,
                'amount_paid' => $expense->amount_paid,
                'balance_due' => $expense->balance_due,
                'payment_method' => $expense->payment_method,
                'reference_number' => $expense->reference_number,
                'description' => $expense->description,
                'notes' => $expense->notes,
                'is_recurring' => $expense->is_recurring,
                'recurring_frequency' => $expense->recurring_frequency,
                'status' => $expense->status,
                'status_label' => $expense->status_label,
                'status_color' => $expense->status_color,
                'payment_status' => $expense->payment_status,
                'payment_status_label' => $expense->payment_status_label,
                'payment_status_color' => $expense->payment_status_color,
                'creator' => $expense->creator ? [
                    'id' => $expense->creator->id,
                    'name' => $expense->creator->name,
                ] : null,
                'approver' => $expense->approver ? [
                    'id' => $expense->approver->id,
                    'name' => $expense->approver->name,
                ] : null,
                'approved_at' => $expense->approved_at?->format('d M Y H:i'),
                'created_at' => $expense->created_at->format('d M Y H:i'),
                'attachments' => $expense->attachments->map(function ($attachment) {
                    return [
                        'id' => $attachment->id,
                        'file_name' => $attachment->file_name,
                        'file_type' => $attachment->file_type,
                        'file_size' => $attachment->file_size_formatted,
                        'url' => $attachment->url,
                        'is_image' => $attachment->is_image,
                        'is_pdf' => $attachment->is_pdf,
                    ];
                }),
                'can_edit' => $expense->canEdit(),
                'can_delete' => $expense->canDelete(),
                'can_approve' => $expense->canApprove(),
                'can_post' => $expense->canPost(),
                'journal_entry' => $expense->journalEntry ? [
                    'id' => $expense->journalEntry->id,
                    'entry_number' => $expense->journalEntry->entry_number,
                    'entry_date' => $expense->journalEntry->entry_date->format('Y-m-d'),
                    'entry_date_formatted' => $expense->journalEntry->entry_date->format('d M Y'),
                    'status' => $expense->journalEntry->status,
                    'status_label' => $expense->journalEntry->status_label,
                    'status_color' => $expense->journalEntry->status_color,
                    'total_debit' => $expense->journalEntry->total_debit,
                    'total_credit' => $expense->journalEntry->total_credit,
                ] : null,
            ],
        ]);
    }

    /**
     * Show edit form.
     */
    public function edit(Expense $expense)
    {
        if (!$expense->canEdit()) {
            return redirect()->route('finance.expenses.show', $expense->id)
                ->with('error', 'This expense cannot be edited.');
        }

        $expense->load(['attachments']);

        $categories = ExpenseCategory::getForSelect();

        $accounts = ChartOfAccount::active()
            ->postable()
            ->where('account_type', 'expense')
            ->orderBy('account_code')
            ->get()
            ->map(fn($a) => ['value' => $a->id, 'label' => $a->account_code . ' - ' . $a->account_name]);

        $paymentAccounts = ChartOfAccount::active()
            ->postable()
            ->whereIn('account_type', ['asset'])
            ->where(function ($q) {
                $q->where('is_bank_account', true)
                    ->orWhere('account_code', 'like', '111%');
            })
            ->orderBy('account_code')
            ->get()
            ->map(fn($a) => ['value' => $a->id, 'label' => $a->account_code . ' - ' . $a->account_name]);

        $suppliers = Customer::where('customer_type', 'supplier')
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(fn($s) => ['value' => $s->id, 'label' => $s->name]);

        return Inertia::render('Finance/Expenses/Edit', [
            'expense' => [
                'id' => $expense->id,
                'expense_number' => $expense->expense_number,
                'expense_date' => $expense->expense_date->format('Y-m-d'),
                'category_id' => $expense->category_id,
                'account_id' => $expense->account_id,
                'paid_from_account_id' => $expense->paid_from_account_id,
                'supplier_id' => $expense->supplier_id,
                'payee_name' => $expense->payee_name,
                'currency_code' => $expense->currency_code,
                'exchange_rate' => $expense->exchange_rate,
                'amount' => $expense->amount,
                'tax_amount' => $expense->tax_amount,
                'payment_method' => $expense->payment_method,
                'reference_number' => $expense->reference_number,
                'description' => $expense->description,
                'notes' => $expense->notes,
                'is_recurring' => $expense->is_recurring,
                'recurring_frequency' => $expense->recurring_frequency,
                'attachments' => $expense->attachments->map(function ($attachment) {
                    return [
                        'id' => $attachment->id,
                        'file_name' => $attachment->file_name,
                        'file_type' => $attachment->file_type,
                        'file_size' => $attachment->file_size_formatted,
                        'url' => $attachment->url,
                    ];
                }),
            ],
            'categories' => $categories,
            'accounts' => $accounts,
            'paymentAccounts' => $paymentAccounts,
            'suppliers' => $suppliers,
        ]);
    }

    /**
     * Update expense.
     */
    public function update(Request $request, Expense $expense)
    {
        if (!$expense->canEdit()) {
            return response()->json([
                'success' => false,
                'message' => 'This expense cannot be edited.',
            ], 422);
        }

        $validated = $request->validate([
            'expense_date' => ['required', 'date'],
            'category_id' => ['required', 'exists:mst_expense_categories,id'],
            'account_id' => ['required', 'exists:fin_chart_of_accounts,id'],
            'paid_from_account_id' => ['nullable', 'exists:fin_chart_of_accounts,id'],
            'supplier_id' => ['nullable', 'exists:mst_customers,id'],
            'payee_name' => ['nullable', 'string', 'max:100'],
            'currency_code' => ['required', 'string', 'max:3'],
            'exchange_rate' => ['required', 'numeric', 'min:0'],
            'amount' => ['required', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'in:cash,bank_transfer,credit_card,check,other'],
            'reference_number' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'is_recurring' => ['boolean'],
            'recurring_frequency' => ['nullable', 'in:daily,weekly,monthly,quarterly,yearly'],
            'remove_attachments' => ['nullable', 'array'],
            'remove_attachments.*' => ['integer'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240'],
        ]);

        DB::beginTransaction();

        try {
            $amount = $validated['amount'];
            $taxAmount = $validated['tax_amount'] ?? 0;
            $exchangeRate = $validated['exchange_rate'];

            $expense->update([
                'expense_date' => $validated['expense_date'],
                'category_id' => $validated['category_id'],
                'account_id' => $validated['account_id'],
                'paid_from_account_id' => $validated['paid_from_account_id'] ?? null,
                'supplier_id' => $validated['supplier_id'] ?? null,
                'payee_name' => $validated['payee_name'] ?? null,
                'currency_code' => $validated['currency_code'],
                'exchange_rate' => $exchangeRate,
                'amount' => $amount,
                'amount_in_base' => bcmul((string) $amount, (string) $exchangeRate, 2),
                'tax_amount' => $taxAmount,
                'total_amount' => bcadd((string) $amount, (string) $taxAmount, 2),
                'payment_method' => $validated['payment_method'] ?? null,
                'reference_number' => $validated['reference_number'] ?? null,
                'description' => $validated['description'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'is_recurring' => $validated['is_recurring'] ?? false,
                'recurring_frequency' => $validated['recurring_frequency'] ?? null,
            ]);

            // Remove attachments
            if (!empty($validated['remove_attachments'])) {
                ExpenseAttachment::whereIn('id', $validated['remove_attachments'])
                    ->where('expense_id', $expense->id)
                    ->get()
                    ->each->delete();
            }

            // Add new attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('expenses/' . $expense->id, 'public');

                    ExpenseAttachment::create([
                        'expense_id' => $expense->id,
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                        'uploaded_by' => Auth::id(),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Expense updated successfully.',
                'expense' => $expense->fresh()->load(['category', 'account', 'attachments']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update expense: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete expense.
     */
    public function destroy(Expense $expense)
    {
        if (!$expense->canDelete()) {
            return response()->json([
                'success' => false,
                'message' => 'This expense cannot be deleted.',
            ], 422);
        }

        // Delete attachments
        foreach ($expense->attachments as $attachment) {
            $attachment->delete();
        }

        $expense->delete();

        return response()->json([
            'success' => true,
            'message' => 'Expense deleted successfully.',
        ]);
    }

    /**
     * Approve expense.
     */
    public function approve(Expense $expense)
    {
        if (!$expense->canApprove()) {
            return response()->json([
                'success' => false,
                'message' => 'This expense cannot be approved.',
            ], 422);
        }

        $expense->approve(Auth::id());

        return response()->json([
            'success' => true,
            'message' => 'Expense approved successfully.',
            'expense' => $expense->fresh(),
        ]);
    }

    /**
     * Post expense (create journal entry).
     */
    public function post(Expense $expense)
    {
        if (!$expense->canPost()) {
            return response()->json([
                'success' => false,
                'message' => 'This expense cannot be posted.',
            ], 422);
        }

        $expenseJournalService = new ExpenseJournalService();
        $result = $expenseJournalService->postExpense($expense);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'expense' => $expense->fresh(['journalEntry']),
            'journal_entry' => $result['journal_entry'] ? [
                'id' => $result['journal_entry']->id,
                'entry_number' => $result['journal_entry']->entry_number,
            ] : null,
        ]);
    }

    /**
     * Cancel expense.
     */
    public function cancel(Expense $expense)
    {
        if (!$expense->cancel()) {
            return response()->json([
                'success' => false,
                'message' => 'This expense cannot be cancelled.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Expense cancelled successfully.',
            'expense' => $expense->fresh(),
        ]);
    }
}

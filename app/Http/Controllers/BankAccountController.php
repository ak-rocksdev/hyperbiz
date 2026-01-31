<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\ChartOfAccount;
use App\Services\BankAccountService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class BankAccountController extends Controller implements HasMiddleware
{
    protected BankAccountService $bankAccountService;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:finance.bank.view', only: ['index', 'show', 'list']),
            new Middleware('permission:finance.bank.manage', only: ['store', 'update', 'destroy', 'storeTransaction', 'deleteTransaction', 'transfer', 'toggleStatus']),
        ];
    }

    public function __construct(BankAccountService $bankAccountService)
    {
        $this->bankAccountService = $bankAccountService;
    }

    /**
     * Display list of bank accounts
     */
    public function index(Request $request)
    {
        $query = BankAccount::with(['glAccount'])
            ->orderBy('bank_name')
            ->orderBy('account_name');

        // Filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('bank_name', 'like', "%{$search}%")
                    ->orWhere('account_name', 'like', "%{$search}%")
                    ->orWhere('account_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('currency')) {
            $query->where('currency_code', $request->currency);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $accounts = $query->paginate($request->get('per_page', 15));

        // Get GL accounts for bank accounts (Cash in Bank type)
        $glAccounts = ChartOfAccount::where('is_bank_account', true)
            ->orWhere('account_code', 'like', '112%')
            ->where('is_active', true)
            ->where('is_header', false)
            ->orderBy('account_code')
            ->get(['id', 'account_code', 'account_name']);

        // Calculate totals by currency
        $totals = BankAccount::selectRaw('currency_code, SUM(current_balance) as total_balance, COUNT(*) as account_count')
            ->where('is_active', true)
            ->groupBy('currency_code')
            ->get();

        return Inertia::render('Finance/BankAccounts/Index', [
            'accounts' => $accounts,
            'glAccounts' => $glAccounts,
            'totals' => $totals,
            'filters' => $request->only(['search', 'currency', 'is_active']),
        ]);
    }

    /**
     * Store new bank account
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gl_account_id' => 'required|exists:fin_chart_of_accounts,id',
            'bank_name' => 'required|string|max:100',
            'account_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'currency_code' => 'required|string|size:3',
            'swift_code' => 'nullable|string|max:20',
            'branch' => 'nullable|string|max:100',
            'opening_balance' => 'nullable|numeric',
            'opening_balance_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $account = $this->bankAccountService->create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Bank account created successfully.',
            'data' => $account,
        ]);
    }

    /**
     * Display bank account details
     */
    public function show(Request $request, BankAccount $bankAccount)
    {
        $bankAccount->load(['glAccount', 'creator', 'updater']);

        // Get transactions with pagination
        $transactions = $this->bankAccountService->getTransactions($bankAccount, [
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'transaction_type' => $request->transaction_type,
            'reconciliation_status' => $request->reconciliation_status,
            'search' => $request->search,
            'per_page' => $request->get('per_page', 25),
        ]);

        // Get summary
        $summary = $this->bankAccountService->getAccountSummary(
            $bankAccount,
            $request->start_date,
            $request->end_date
        );

        // Get recent reconciliations
        $recentReconciliations = $bankAccount->reconciliations()
            ->with('completedBy')
            ->orderBy('statement_date', 'desc')
            ->limit(5)
            ->get();

        return Inertia::render('Finance/BankAccounts/Detail', [
            'bankAccount' => $bankAccount,
            'transactions' => $transactions,
            'summary' => $summary,
            'recentReconciliations' => $recentReconciliations,
            'transactionTypes' => BankTransaction::getTypes(),
            'reconciliationStatuses' => BankTransaction::getStatuses(),
            'filters' => $request->only(['start_date', 'end_date', 'transaction_type', 'reconciliation_status', 'search']),
        ]);
    }

    /**
     * Update bank account
     */
    public function update(Request $request, BankAccount $bankAccount)
    {
        $validator = Validator::make($request->all(), [
            'gl_account_id' => 'required|exists:fin_chart_of_accounts,id',
            'bank_name' => 'required|string|max:100',
            'account_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'currency_code' => 'required|string|size:3',
            'swift_code' => 'nullable|string|max:20',
            'branch' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $account = $this->bankAccountService->update($bankAccount, $validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Bank account updated successfully.',
            'data' => $account,
        ]);
    }

    /**
     * Delete bank account
     */
    public function destroy(BankAccount $bankAccount)
    {
        // Check if account has transactions
        if ($bankAccount->transactions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete account with existing transactions.',
            ], 422);
        }

        $bankAccount->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bank account deleted successfully.',
        ]);
    }

    /**
     * Store new transaction
     */
    public function storeTransaction(Request $request, BankAccount $bankAccount)
    {
        $validator = Validator::make($request->all(), [
            'transaction_date' => 'required|date',
            'transaction_type' => 'required|in:deposit,withdrawal,fee,interest,adjustment',
            'amount' => 'required|numeric|min:0.01',
            'reference' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:255',
            'payee' => 'nullable|string|max:100',
            'check_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['source_type'] = 'manual';

        $transaction = $this->bankAccountService->recordTransaction($bankAccount, $data);

        return response()->json([
            'success' => true,
            'message' => 'Transaction recorded successfully.',
            'data' => $transaction,
            'new_balance' => $bankAccount->fresh()->current_balance,
        ]);
    }

    /**
     * Delete transaction
     */
    public function deleteTransaction(BankAccount $bankAccount, BankTransaction $transaction)
    {
        if ($transaction->bank_account_id !== $bankAccount->id) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction does not belong to this account.',
            ], 422);
        }

        if ($transaction->reconciliation_status !== BankTransaction::STATUS_UNRECONCILED) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a reconciled transaction.',
            ], 422);
        }

        $this->bankAccountService->deleteTransaction($transaction);

        return response()->json([
            'success' => true,
            'message' => 'Transaction deleted successfully.',
            'new_balance' => $bankAccount->fresh()->current_balance,
        ]);
    }

    /**
     * Record transfer between accounts
     */
    public function transfer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_account_id' => 'required|exists:fin_bank_accounts,id',
            'to_account_id' => 'required|exists:fin_bank_accounts,id|different:from_account_id',
            'transaction_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $fromAccount = BankAccount::findOrFail($request->from_account_id);
        $toAccount = BankAccount::findOrFail($request->to_account_id);

        $result = $this->bankAccountService->recordTransfer($fromAccount, $toAccount, $validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Transfer recorded successfully.',
            'data' => $result,
        ]);
    }

    /**
     * Toggle account status
     */
    public function toggleStatus(BankAccount $bankAccount)
    {
        $bankAccount->update(['is_active' => !$bankAccount->is_active]);

        return response()->json([
            'success' => true,
            'message' => $bankAccount->is_active ? 'Account activated.' : 'Account deactivated.',
            'is_active' => $bankAccount->is_active,
        ]);
    }

    /**
     * Get accounts for dropdown (API)
     */
    public function list(Request $request)
    {
        $query = BankAccount::active()
            ->orderBy('bank_name')
            ->orderBy('account_name');

        if ($request->filled('currency')) {
            $query->forCurrency($request->currency);
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(['id', 'bank_name', 'account_name', 'account_number', 'currency_code', 'current_balance']),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Services\BankReconciliationService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class BankReconciliationController extends Controller implements HasMiddleware
{
    protected BankReconciliationService $reconciliationService;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:finance.bank.view', only: ['index', 'show', 'summary']),
            new Middleware('permission:finance.bank.reconcile', only: ['reconcile', 'start', 'match', 'unmatch', 'complete', 'cancel', 'adjustment']),
        ];
    }

    public function __construct(BankReconciliationService $reconciliationService)
    {
        $this->reconciliationService = $reconciliationService;
    }

    /**
     * Display reconciliation history
     */
    public function index(Request $request)
    {
        $query = BankReconciliation::with(['bankAccount', 'creator', 'completedBy'])
            ->orderBy('statement_date', 'desc');

        if ($request->filled('bank_account_id')) {
            $query->where('bank_account_id', $request->bank_account_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $reconciliations = $query->paginate($request->get('per_page', 15));

        $bankAccounts = BankAccount::active()
            ->orderBy('bank_name')
            ->get(['id', 'bank_name', 'account_name']);

        return Inertia::render('Finance/BankReconciliations/Index', [
            'reconciliations' => $reconciliations,
            'bankAccounts' => $bankAccounts,
            'statuses' => BankReconciliation::getStatuses(),
            'filters' => $request->only(['bank_account_id', 'status']),
        ]);
    }

    /**
     * Show reconciliation wizard
     */
    public function reconcile(Request $request, BankAccount $bankAccount)
    {
        // Check for existing in-progress reconciliation
        $existingReconciliation = $bankAccount->reconciliations()
            ->where('status', BankReconciliation::STATUS_IN_PROGRESS)
            ->first();

        if ($existingReconciliation) {
            return $this->show($existingReconciliation);
        }

        return Inertia::render('Finance/BankReconciliations/Start', [
            'bankAccount' => $bankAccount->load('glAccount'),
            'lastReconciliation' => $bankAccount->reconciliations()
                ->where('status', BankReconciliation::STATUS_COMPLETED)
                ->orderBy('statement_date', 'desc')
                ->first(),
        ]);
    }

    /**
     * Start new reconciliation
     */
    public function start(Request $request, BankAccount $bankAccount)
    {
        $validator = Validator::make($request->all(), [
            'statement_date' => 'required|date',
            'statement_ending_balance' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $reconciliation = $this->reconciliationService->startReconciliation(
            $bankAccount,
            $validator->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Reconciliation started.',
            'reconciliation_id' => $reconciliation->id,
        ]);
    }

    /**
     * Show reconciliation in progress
     */
    public function show(BankReconciliation $reconciliation)
    {
        $reconciliation->load(['bankAccount.glAccount', 'creator', 'completedBy']);

        // Get summary
        $summary = $this->reconciliationService->getReconciliationSummary($reconciliation);

        // Get unreconciled transactions
        $unreconciledTransactions = $this->reconciliationService->getUnreconciledTransactions(
            $reconciliation->bankAccount,
            $reconciliation->statement_date->format('Y-m-d')
        );

        // Get matched transactions
        $matchedTransactions = $reconciliation->transactions()
            ->orderBy('transaction_date')
            ->get();

        return Inertia::render('Finance/BankReconciliations/Reconcile', [
            'reconciliation' => $reconciliation,
            'summary' => $summary,
            'unreconciledTransactions' => $unreconciledTransactions,
            'matchedTransactions' => $matchedTransactions,
        ]);
    }

    /**
     * Match transactions
     */
    public function match(Request $request, BankReconciliation $reconciliation)
    {
        if ($reconciliation->status !== BankReconciliation::STATUS_IN_PROGRESS) {
            return response()->json([
                'success' => false,
                'message' => 'This reconciliation is not in progress.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'transaction_ids' => 'required|array',
            'transaction_ids.*' => 'exists:fin_bank_transactions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->reconciliationService->matchTransactions(
            $reconciliation,
            $request->transaction_ids
        );

        return response()->json([
            'success' => true,
            'message' => "{$result['matched_count']} transaction(s) matched.",
            'difference' => $result['difference'],
        ]);
    }

    /**
     * Unmatch a transaction
     */
    public function unmatch(BankReconciliation $reconciliation, int $transactionId)
    {
        if ($reconciliation->status !== BankReconciliation::STATUS_IN_PROGRESS) {
            return response()->json([
                'success' => false,
                'message' => 'This reconciliation is not in progress.',
            ], 422);
        }

        $result = $this->reconciliationService->unmatchTransaction($reconciliation, $transactionId);

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found or not matched to this reconciliation.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Transaction unmatched.',
            'difference' => $reconciliation->fresh()->difference,
        ]);
    }

    /**
     * Complete reconciliation
     */
    public function complete(BankReconciliation $reconciliation)
    {
        if ($reconciliation->status !== BankReconciliation::STATUS_IN_PROGRESS) {
            return response()->json([
                'success' => false,
                'message' => 'This reconciliation is not in progress.',
            ], 422);
        }

        $result = $this->reconciliationService->completeReconciliation($reconciliation);

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }

    /**
     * Cancel reconciliation
     */
    public function cancel(BankReconciliation $reconciliation)
    {
        $result = $this->reconciliationService->cancelReconciliation($reconciliation);

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }

    /**
     * Create adjustment to balance reconciliation
     */
    public function adjustment(Request $request, BankReconciliation $reconciliation)
    {
        if ($reconciliation->status !== BankReconciliation::STATUS_IN_PROGRESS) {
            return response()->json([
                'success' => false,
                'message' => 'This reconciliation is not in progress.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'description' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $transaction = $this->reconciliationService->createAdjustment(
            $reconciliation,
            $validator->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Adjustment created.',
            'transaction' => $transaction,
            'difference' => $reconciliation->fresh()->difference,
        ]);
    }

    /**
     * Get reconciliation summary (API)
     */
    public function summary(BankReconciliation $reconciliation)
    {
        $summary = $this->reconciliationService->getReconciliationSummary($reconciliation);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }
}

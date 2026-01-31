<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Models\BankTransaction;
use Illuminate\Support\Facades\DB;

class BankReconciliationService
{
    /**
     * Start a new reconciliation session
     */
    public function startReconciliation(BankAccount $account, array $data): BankReconciliation
    {
        // Check for existing in-progress reconciliation
        $existing = $account->reconciliations()
            ->where('status', BankReconciliation::STATUS_IN_PROGRESS)
            ->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($account, $data) {
            $reconciliation = BankReconciliation::create([
                'bank_account_id' => $account->id,
                'statement_date' => $data['statement_date'],
                'reconciliation_date' => now(),
                'statement_ending_balance' => $data['statement_ending_balance'],
                'book_balance' => $account->current_balance,
                'reconciled_balance' => $account->current_balance,
                'difference' => bcsub((string) $data['statement_ending_balance'], (string) $account->current_balance, 2),
                'status' => BankReconciliation::STATUS_IN_PROGRESS,
            ]);

            return $reconciliation;
        });
    }

    /**
     * Get unreconciled transactions for matching
     */
    public function getUnreconciledTransactions(BankAccount $account, ?string $beforeDate = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = $account->transactions()
            ->where('reconciliation_status', BankTransaction::STATUS_UNRECONCILED)
            ->orderBy('transaction_date')
            ->orderBy('id');

        if ($beforeDate) {
            $query->where('transaction_date', '<=', $beforeDate);
        }

        return $query->get();
    }

    /**
     * Mark transactions as matched in reconciliation
     */
    public function matchTransactions(BankReconciliation $reconciliation, array $transactionIds): array
    {
        $matchedCount = 0;

        DB::transaction(function () use ($reconciliation, $transactionIds, &$matchedCount) {
            foreach ($transactionIds as $id) {
                $transaction = BankTransaction::find($id);

                if ($transaction && $transaction->bank_account_id === $reconciliation->bank_account_id) {
                    $transaction->update([
                        'reconciliation_id' => $reconciliation->id,
                        'reconciliation_status' => BankTransaction::STATUS_MATCHED,
                    ]);
                    $matchedCount++;
                }
            }

            // Recalculate difference
            $this->calculateReconciliationBalance($reconciliation);
        });

        return [
            'matched_count' => $matchedCount,
            'difference' => $reconciliation->fresh()->difference,
        ];
    }

    /**
     * Unmatch a transaction from reconciliation
     */
    public function unmatchTransaction(BankReconciliation $reconciliation, int $transactionId): bool
    {
        return DB::transaction(function () use ($reconciliation, $transactionId) {
            $transaction = BankTransaction::where('id', $transactionId)
                ->where('reconciliation_id', $reconciliation->id)
                ->first();

            if (!$transaction) {
                return false;
            }

            $transaction->update([
                'reconciliation_id' => null,
                'reconciliation_status' => BankTransaction::STATUS_UNRECONCILED,
            ]);

            // Recalculate difference
            $this->calculateReconciliationBalance($reconciliation);

            return true;
        });
    }

    /**
     * Calculate reconciliation balance and difference
     */
    public function calculateReconciliationBalance(BankReconciliation $reconciliation): array
    {
        $account = $reconciliation->bankAccount;

        // Get the last reconciled balance as starting point
        $startingBalance = $account->last_reconciled_balance ?? 0;

        // Calculate cleared balance from matched transactions
        $matchedDeposits = $reconciliation->transactions()
            ->whereIn('transaction_type', ['deposit', 'transfer_in', 'interest'])
            ->sum('amount');

        $matchedWithdrawals = $reconciliation->transactions()
            ->whereIn('transaction_type', ['withdrawal', 'transfer_out', 'fee', 'adjustment'])
            ->sum('amount');

        $clearedBalance = bcsub(
            bcadd((string) $startingBalance, (string) $matchedDeposits, 2),
            (string) $matchedWithdrawals,
            2
        );
        $difference = bcsub((string) $reconciliation->statement_ending_balance, $clearedBalance, 2);

        $reconciliation->update([
            'reconciled_balance' => $clearedBalance,
            'difference' => $difference,
        ]);

        return [
            'starting_balance' => $startingBalance,
            'matched_deposits' => $matchedDeposits,
            'matched_withdrawals' => $matchedWithdrawals,
            'cleared_balance' => $clearedBalance,
            'statement_balance' => $reconciliation->statement_ending_balance,
            'difference' => $difference,
        ];
    }

    /**
     * Get reconciliation summary
     */
    public function getReconciliationSummary(BankReconciliation $reconciliation): array
    {
        $account = $reconciliation->bankAccount;

        $unreconciledTransactions = $this->getUnreconciledTransactions(
            $account,
            $reconciliation->statement_date->format('Y-m-d')
        );

        $matchedTransactions = $reconciliation->transactions()->get();

        $unreconciledDeposits = $unreconciledTransactions
            ->filter(fn($t) => $t->is_deposit)
            ->sum('amount');

        $unreconciledWithdrawals = $unreconciledTransactions
            ->filter(fn($t) => $t->is_withdrawal)
            ->sum('amount');

        $matchedDeposits = $matchedTransactions
            ->filter(fn($t) => $t->is_deposit)
            ->sum('amount');

        $matchedWithdrawals = $matchedTransactions
            ->filter(fn($t) => $t->is_withdrawal)
            ->sum('amount');

        $balanceInfo = $this->calculateReconciliationBalance($reconciliation);

        return [
            'statement_date' => $reconciliation->statement_date,
            'statement_ending_balance' => $reconciliation->statement_ending_balance,
            'book_balance' => $account->current_balance,
            'last_reconciled_balance' => $account->last_reconciled_balance,
            'last_reconciled_date' => $account->last_reconciled_date,
            'unreconciled' => [
                'count' => $unreconciledTransactions->count(),
                'deposits' => $unreconciledDeposits,
                'withdrawals' => $unreconciledWithdrawals,
            ],
            'matched' => [
                'count' => $matchedTransactions->count(),
                'deposits' => $matchedDeposits,
                'withdrawals' => $matchedWithdrawals,
            ],
            'cleared_balance' => $balanceInfo['cleared_balance'],
            'difference' => $balanceInfo['difference'],
            'is_balanced' => bccomp((string) abs((float) $balanceInfo['difference']), '0.01', 2) < 0,
        ];
    }

    /**
     * Complete the reconciliation
     */
    public function completeReconciliation(BankReconciliation $reconciliation): array
    {
        $summary = $this->getReconciliationSummary($reconciliation);

        if (!$summary['is_balanced']) {
            return [
                'success' => false,
                'message' => "Reconciliation has a difference of {$summary['difference']}. Cannot complete until balanced.",
            ];
        }

        $reconciliation->complete();

        return [
            'success' => true,
            'message' => 'Reconciliation completed successfully.',
            'reconciliation' => $reconciliation->fresh(),
        ];
    }

    /**
     * Cancel reconciliation
     */
    public function cancelReconciliation(BankReconciliation $reconciliation): array
    {
        if ($reconciliation->status === BankReconciliation::STATUS_COMPLETED) {
            return [
                'success' => false,
                'message' => 'Cannot cancel a completed reconciliation.',
            ];
        }

        $reconciliation->cancel();

        return [
            'success' => true,
            'message' => 'Reconciliation cancelled.',
        ];
    }

    /**
     * Create adjustment transaction to balance reconciliation
     */
    public function createAdjustment(BankReconciliation $reconciliation, array $data): BankTransaction
    {
        $account = $reconciliation->bankAccount;
        $difference = $reconciliation->difference;

        return DB::transaction(function () use ($account, $reconciliation, $difference, $data) {
            $transactionType = $difference > 0 ? BankTransaction::TYPE_DEPOSIT : BankTransaction::TYPE_WITHDRAWAL;

            $transaction = $account->transactions()->create([
                'transaction_date' => $reconciliation->statement_date,
                'description' => $data['description'] ?? 'Reconciliation Adjustment',
                'transaction_type' => BankTransaction::TYPE_ADJUSTMENT,
                'amount' => abs($difference),
                'source_type' => 'reconciliation_adjustment',
                'source_id' => $reconciliation->id,
                'reconciliation_id' => $reconciliation->id,
                'reconciliation_status' => BankTransaction::STATUS_MATCHED,
                'notes' => $data['notes'] ?? null,
            ]);

            // Recalculate balance
            $this->calculateReconciliationBalance($reconciliation);

            return $transaction;
        });
    }

    /**
     * Get reconciliation history for an account
     */
    public function getReconciliationHistory(BankAccount $account, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return $account->reconciliations()
            ->with(['creator', 'completedBy'])
            ->orderBy('statement_date', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Auto-match transactions based on amount and date proximity
     */
    public function autoMatch(BankReconciliation $reconciliation, array $statementItems): array
    {
        $matchedCount = 0;
        $unmatchedItems = [];

        $unreconciledTransactions = $this->getUnreconciledTransactions(
            $reconciliation->bankAccount,
            $reconciliation->statement_date->format('Y-m-d')
        )->keyBy('id');

        foreach ($statementItems as $item) {
            $match = null;

            // Find exact match by amount and date (using bcmath for precision)
            foreach ($unreconciledTransactions as $transaction) {
                $amountDiff = bcsub((string) $transaction->signed_amount, (string) $item['amount'], 2);
                $amountMatch = bccomp((string) abs((float) $amountDiff), '0.01', 2) < 0;
                $dateMatch = $transaction->transaction_date->format('Y-m-d') === $item['date'];

                if ($amountMatch && $dateMatch) {
                    $match = $transaction;
                    break;
                }
            }

            // If no exact match, try matching by amount within 3-day window
            if (!$match) {
                $itemDate = \Carbon\Carbon::parse($item['date']);

                foreach ($unreconciledTransactions as $transaction) {
                    $amountDiff = bcsub((string) $transaction->signed_amount, (string) $item['amount'], 2);
                    $amountMatch = bccomp((string) abs((float) $amountDiff), '0.01', 2) < 0;
                    $withinWindow = abs($transaction->transaction_date->diffInDays($itemDate)) <= 3;

                    if ($amountMatch && $withinWindow) {
                        $match = $transaction;
                        break;
                    }
                }
            }

            if ($match) {
                $match->update([
                    'reconciliation_id' => $reconciliation->id,
                    'reconciliation_status' => BankTransaction::STATUS_MATCHED,
                ]);
                $unreconciledTransactions->forget($match->id);
                $matchedCount++;
            } else {
                $unmatchedItems[] = $item;
            }
        }

        $this->calculateReconciliationBalance($reconciliation);

        return [
            'matched_count' => $matchedCount,
            'unmatched_items' => $unmatchedItems,
            'difference' => $reconciliation->fresh()->difference,
        ];
    }
}

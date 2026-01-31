<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use Illuminate\Support\Facades\DB;

class BankAccountService
{
    /**
     * Create a new bank account
     */
    public function create(array $data): BankAccount
    {
        return DB::transaction(function () use ($data) {
            $account = BankAccount::create($data);

            // If opening balance is provided, create initial transaction
            if (isset($data['opening_balance']) && $data['opening_balance'] != 0) {
                $this->recordTransaction($account, [
                    'transaction_date' => $data['opening_balance_date'] ?? now(),
                    'description' => 'Opening Balance',
                    'transaction_type' => $data['opening_balance'] > 0 ? 'deposit' : 'withdrawal',
                    'amount' => abs($data['opening_balance']),
                    'source_type' => 'opening_balance',
                ]);
            }

            return $account->fresh();
        });
    }

    /**
     * Update bank account
     */
    public function update(BankAccount $account, array $data): BankAccount
    {
        $account->update($data);
        return $account->fresh();
    }

    /**
     * Record a bank transaction
     */
    public function recordTransaction(BankAccount $account, array $data): BankTransaction
    {
        return DB::transaction(function () use ($account, $data) {
            $transaction = $account->transactions()->create([
                'transaction_date' => $data['transaction_date'],
                'reference' => $data['reference'] ?? null,
                'description' => $data['description'] ?? null,
                'transaction_type' => $data['transaction_type'],
                'amount' => $data['amount'],
                'source_type' => $data['source_type'] ?? 'manual',
                'source_id' => $data['source_id'] ?? null,
                'journal_entry_id' => $data['journal_entry_id'] ?? null,
                'payee' => $data['payee'] ?? null,
                'check_number' => $data['check_number'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Update running balance
            $this->updateRunningBalances($account, $transaction->transaction_date);

            return $transaction->fresh();
        });
    }

    /**
     * Record deposit
     */
    public function recordDeposit(BankAccount $account, array $data): BankTransaction
    {
        $data['transaction_type'] = BankTransaction::TYPE_DEPOSIT;
        return $this->recordTransaction($account, $data);
    }

    /**
     * Record withdrawal
     */
    public function recordWithdrawal(BankAccount $account, array $data): BankTransaction
    {
        $data['transaction_type'] = BankTransaction::TYPE_WITHDRAWAL;
        return $this->recordTransaction($account, $data);
    }

    /**
     * Record transfer between accounts
     */
    public function recordTransfer(
        BankAccount $fromAccount,
        BankAccount $toAccount,
        array $data
    ): array {
        return DB::transaction(function () use ($fromAccount, $toAccount, $data) {
            // Record withdrawal from source
            $withdrawal = $fromAccount->transactions()->create([
                'transaction_date' => $data['transaction_date'],
                'reference' => $data['reference'] ?? null,
                'description' => "Transfer to {$toAccount->account_name}",
                'transaction_type' => BankTransaction::TYPE_TRANSFER_OUT,
                'amount' => $data['amount'],
                'source_type' => 'transfer',
                'notes' => $data['notes'] ?? null,
            ]);

            // Record deposit to destination
            $deposit = $toAccount->transactions()->create([
                'transaction_date' => $data['transaction_date'],
                'reference' => $data['reference'] ?? null,
                'description' => "Transfer from {$fromAccount->account_name}",
                'transaction_type' => BankTransaction::TYPE_TRANSFER_IN,
                'amount' => $data['amount'],
                'source_type' => 'transfer',
                'source_id' => $withdrawal->id,
                'notes' => $data['notes'] ?? null,
            ]);

            // Link transactions
            $withdrawal->update(['source_id' => $deposit->id]);

            // Update running balances
            $this->updateRunningBalances($fromAccount, $data['transaction_date']);
            $this->updateRunningBalances($toAccount, $data['transaction_date']);

            return [
                'withdrawal' => $withdrawal->fresh(),
                'deposit' => $deposit->fresh(),
            ];
        });
    }

    /**
     * Delete transaction
     */
    public function deleteTransaction(BankTransaction $transaction): bool
    {
        return DB::transaction(function () use ($transaction) {
            $account = $transaction->bankAccount;
            $date = $transaction->transaction_date;

            $transaction->delete();

            // Update running balances from the deleted transaction date
            $this->updateRunningBalances($account, $date);

            return true;
        });
    }

    /**
     * Update running balances for all transactions from a given date
     */
    public function updateRunningBalances(BankAccount $account, $fromDate = null): void
    {
        $query = $account->transactions()->orderBy('transaction_date')->orderBy('id');

        if ($fromDate) {
            // Get balance before the fromDate
            $previousBalance = $account->transactions()
                ->where('transaction_date', '<', $fromDate)
                ->selectRaw('SUM(CASE WHEN transaction_type IN (\'deposit\', \'transfer_in\', \'interest\') THEN amount ELSE -amount END) as balance')
                ->value('balance') ?? 0;

            $query->where('transaction_date', '>=', $fromDate);
        } else {
            $previousBalance = 0;
        }

        $runningBalance = $previousBalance;

        foreach ($query->get() as $transaction) {
            if ($transaction->is_deposit) {
                $runningBalance += $transaction->amount;
            } else {
                $runningBalance -= $transaction->amount;
            }

            $transaction->update(['running_balance' => $runningBalance]);
        }
    }

    /**
     * Get account summary
     */
    public function getAccountSummary(BankAccount $account, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = $account->transactions();

        if ($startDate) {
            $query->where('transaction_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('transaction_date', '<=', $endDate);
        }

        $deposits = (clone $query)->deposits()->sum('amount');
        $withdrawals = (clone $query)->withdrawals()->sum('amount');
        $transactionCount = $query->count();
        $unreconciledCount = (clone $query)->unreconciled()->count();

        return [
            'current_balance' => $account->current_balance,
            'deposits' => $deposits,
            'withdrawals' => $withdrawals,
            'net_change' => $deposits - $withdrawals,
            'transaction_count' => $transactionCount,
            'unreconciled_count' => $unreconciledCount,
            'last_reconciled_date' => $account->last_reconciled_date,
            'last_reconciled_balance' => $account->last_reconciled_balance,
        ];
    }

    /**
     * Get transactions for listing
     */
    public function getTransactions(BankAccount $account, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $account->transactions()
            ->with(['journalEntry', 'reconciliation'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc');

        if (!empty($filters['start_date'])) {
            $query->where('transaction_date', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->where('transaction_date', '<=', $filters['end_date']);
        }
        if (!empty($filters['transaction_type'])) {
            $query->where('transaction_type', $filters['transaction_type']);
        }
        if (!empty($filters['reconciliation_status'])) {
            $query->where('reconciliation_status', $filters['reconciliation_status']);
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhere('payee', 'like', "%{$search}%");
            });
        }

        $perPage = $filters['per_page'] ?? 25;

        return $query->paginate($perPage);
    }

    /**
     * Import bank statement (CSV format)
     */
    public function importStatement(BankAccount $account, array $transactions): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($transactions as $index => $data) {
                try {
                    // Check for duplicates based on date, amount, and reference
                    $exists = $account->transactions()
                        ->where('transaction_date', $data['transaction_date'])
                        ->where('amount', $data['amount'])
                        ->where('reference', $data['reference'] ?? null)
                        ->exists();

                    if ($exists) {
                        $skipped++;
                        continue;
                    }

                    $this->recordTransaction($account, $data);
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Row {$index}: {$e->getMessage()}";
                }
            }

            DB::commit();

            return [
                'success' => true,
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'imported' => 0,
                'skipped' => 0,
                'errors' => [$e->getMessage()],
            ];
        }
    }
}

<?php

namespace App\Services;

use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * AutoJournalService - Base service for creating auto-generated journal entries
 *
 * This service provides common functionality for creating journal entries
 * from various business transactions (expenses, sales, purchases, payments).
 */
class AutoJournalService
{
    /**
     * Create a journal entry with lines
     *
     * @param array $headerData Journal header data (entry_date, entry_type, reference_type, reference_id, memo, etc.)
     * @param array $lines Array of line data, each with: account_id, description, debit_amount, credit_amount
     * @param bool $autoPost Whether to auto-post the entry after creation
     * @return JournalEntry|null
     */
    public function createJournalEntry(array $headerData, array $lines, bool $autoPost = true): ?JournalEntry
    {
        // Validate lines balance
        if (!$this->validateBalance($lines)) {
            return null;
        }

        // Get fiscal period for the entry date
        $entryDate = Carbon::parse($headerData['entry_date'] ?? now());
        $fiscalPeriod = FiscalPeriod::getByDate($entryDate);

        if (!$fiscalPeriod || !$fiscalPeriod->is_postable) {
            return null;
        }

        return DB::transaction(function () use ($headerData, $lines, $autoPost, $fiscalPeriod) {
            // Calculate totals
            $totalDebit = 0;
            $totalCredit = 0;
            foreach ($lines as $line) {
                $totalDebit += $line['debit_amount'] ?? 0;
                $totalCredit += $line['credit_amount'] ?? 0;
            }

            // Create journal entry header
            $journalEntry = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber(),
                'entry_date' => $headerData['entry_date'] ?? now()->toDateString(),
                'fiscal_period_id' => $fiscalPeriod->id,
                'entry_type' => $headerData['entry_type'] ?? 'manual',
                'reference_type' => $headerData['reference_type'] ?? null,
                'reference_id' => $headerData['reference_id'] ?? null,
                'memo' => $headerData['memo'] ?? null,
                'currency_code' => $headerData['currency_code'] ?? 'IDR',
                'exchange_rate' => $headerData['exchange_rate'] ?? 1,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'status' => 'draft',
                'created_by' => Auth::id(),
            ]);

            // Create journal entry lines
            $lineNumber = 1;
            $exchangeRate = $headerData['exchange_rate'] ?? 1;

            foreach ($lines as $lineData) {
                $debitAmount = $lineData['debit_amount'] ?? 0;
                $creditAmount = $lineData['credit_amount'] ?? 0;

                JournalEntryLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $lineData['account_id'],
                    'line_number' => $lineNumber++,
                    'description' => $lineData['description'] ?? null,
                    'debit_amount' => $debitAmount,
                    'credit_amount' => $creditAmount,
                    'debit_amount_base' => $debitAmount * $exchangeRate,
                    'credit_amount_base' => $creditAmount * $exchangeRate,
                    'customer_id' => $lineData['customer_id'] ?? null,
                    'supplier_id' => $lineData['supplier_id'] ?? null,
                    'product_id' => $lineData['product_id'] ?? null,
                    'expense_id' => $lineData['expense_id'] ?? null,
                ]);
            }

            // Auto-post if requested
            if ($autoPost && $journalEntry->canPost()) {
                $journalEntry->post(Auth::id());
            }

            return $journalEntry->fresh(['lines']);
        });
    }

    /**
     * Validate that debits equal credits
     *
     * @param array $lines
     * @return bool
     */
    protected function validateBalance(array $lines): bool
    {
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($lines as $line) {
            $totalDebit += $line['debit_amount'] ?? 0;
            $totalCredit += $line['credit_amount'] ?? 0;
        }

        return bccomp($totalDebit, $totalCredit, 2) === 0;
    }

    /**
     * Void a journal entry created by auto-journal process
     *
     * @param JournalEntry $journalEntry
     * @param string $reason
     * @return bool
     */
    public function voidJournalEntry(JournalEntry $journalEntry, string $reason): bool
    {
        if (!$journalEntry->canVoid()) {
            return false;
        }

        return $journalEntry->void(Auth::id(), $reason);
    }

    /**
     * Find journal entry by reference
     *
     * @param string $referenceType
     * @param int $referenceId
     * @return JournalEntry|null
     */
    public function findByReference(string $referenceType, int $referenceId): ?JournalEntry
    {
        return JournalEntry::where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->first();
    }

    /**
     * Check if journal entry already exists for a reference
     *
     * @param string $referenceType
     * @param int $referenceId
     * @return bool
     */
    public function hasJournalEntry(string $referenceType, int $referenceId): bool
    {
        return JournalEntry::where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->whereIn('status', ['draft', 'posted'])
            ->exists();
    }
}

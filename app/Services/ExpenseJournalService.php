<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\FinancialSetting;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

/**
 * ExpenseJournalService - Handles expense posting to journal entries
 *
 * Creates double-entry journal entries when expenses are posted:
 * - Debit: Expense Account (from expense.account_id)
 * - Credit: Cash/Bank Account (from expense.paid_from_account_id) or Accounts Payable
 */
class ExpenseJournalService extends AutoJournalService
{
    /**
     * Create journal entry for an expense
     *
     * @param Expense $expense
     * @return JournalEntry|null
     */
    public function createFromExpense(Expense $expense): ?JournalEntry
    {
        // Check if auto-journaling for expenses is enabled
        if (!FinancialSetting::isEnabled('auto_journal_expense')) {
            return null;
        }

        // Check if journal entry already exists
        if ($this->hasJournalEntry('expense', $expense->id)) {
            return $this->findByReference('expense', $expense->id);
        }

        // Validate expense has required accounts
        if (!$expense->account_id) {
            return null;
        }

        // Build journal lines
        $lines = $this->buildExpenseJournalLines($expense);

        if (empty($lines)) {
            return null;
        }

        // Create journal entry
        $journalEntry = $this->createJournalEntry([
            'entry_date' => $expense->expense_date->toDateString(),
            'entry_type' => 'auto_expense',
            'reference_type' => 'expense',
            'reference_id' => $expense->id,
            'memo' => $this->buildMemo($expense),
            'currency_code' => $expense->currency_code,
            'exchange_rate' => $expense->exchange_rate,
        ], $lines, true);

        // Link journal entry to expense
        if ($journalEntry) {
            $expense->update(['journal_entry_id' => $journalEntry->id]);
        }

        return $journalEntry;
    }

    /**
     * Build journal entry lines for an expense
     *
     * Standard expense entry:
     * - Debit: Expense Account (6xxx) - total_amount
     * - Credit: Cash/Bank Account (1xxx) OR Accounts Payable (2110) - total_amount
     *
     * If tax is involved (future enhancement):
     * - Debit: Expense Account - amount
     * - Debit: PPN Input Account - tax_amount
     * - Credit: Cash/Bank/AP - total_amount
     *
     * @param Expense $expense
     * @return array
     */
    protected function buildExpenseJournalLines(Expense $expense): array
    {
        $lines = [];
        $totalAmount = (float) $expense->total_amount;
        $amount = (float) $expense->amount;
        $taxAmount = (float) $expense->tax_amount;

        // Get default AP account if paid_from_account is not set
        $creditAccountId = $expense->paid_from_account_id;

        if (!$creditAccountId) {
            // Use default AP account from settings
            $creditAccountId = FinancialSetting::get('default_ap_account');
        }

        if (!$creditAccountId) {
            return []; // No credit account available
        }

        // Line 1: Debit Expense Account
        if ($taxAmount > 0) {
            // Separate expense and tax
            $lines[] = [
                'account_id' => $expense->account_id,
                'description' => $expense->description ?? 'Expense - ' . $expense->expense_number,
                'debit_amount' => $amount,
                'credit_amount' => 0,
                'expense_id' => $expense->id,
                'supplier_id' => $expense->supplier_id,
            ];

            // Line 2: Debit PPN Input (if PPN is enabled)
            $ppnInputAccount = FinancialSetting::get('default_ppn_input_account');
            if ($ppnInputAccount && FinancialSetting::isEnabled('tax_ppn_enabled')) {
                $lines[] = [
                    'account_id' => $ppnInputAccount,
                    'description' => 'PPN Input - ' . $expense->expense_number,
                    'debit_amount' => $taxAmount,
                    'credit_amount' => 0,
                    'expense_id' => $expense->id,
                ];
            } else {
                // Add tax to expense if no separate PPN account
                $lines[0]['debit_amount'] = $totalAmount;
            }
        } else {
            // No tax - single debit line
            $lines[] = [
                'account_id' => $expense->account_id,
                'description' => $expense->description ?? 'Expense - ' . $expense->expense_number,
                'debit_amount' => $totalAmount,
                'credit_amount' => 0,
                'expense_id' => $expense->id,
                'supplier_id' => $expense->supplier_id,
            ];
        }

        // Credit line: Cash/Bank or AP
        $lines[] = [
            'account_id' => $creditAccountId,
            'description' => $this->buildCreditDescription($expense),
            'debit_amount' => 0,
            'credit_amount' => $totalAmount,
            'expense_id' => $expense->id,
            'supplier_id' => $expense->supplier_id,
        ];

        return $lines;
    }

    /**
     * Build memo for expense journal entry
     *
     * @param Expense $expense
     * @return string
     */
    protected function buildMemo(Expense $expense): string
    {
        $parts = ['Expense: ' . $expense->expense_number];

        if ($expense->supplier) {
            $parts[] = $expense->supplier->name;
        } elseif ($expense->payee_name) {
            $parts[] = $expense->payee_name;
        }

        if ($expense->reference_number) {
            $parts[] = 'Ref: ' . $expense->reference_number;
        }

        return implode(' - ', $parts);
    }

    /**
     * Build description for credit line
     *
     * @param Expense $expense
     * @return string
     */
    protected function buildCreditDescription(Expense $expense): string
    {
        $paymentMethod = $expense->payment_method ?? 'payment';
        $paymentMethodLabels = [
            'cash' => 'Cash Payment',
            'bank_transfer' => 'Bank Transfer',
            'credit_card' => 'Credit Card',
            'check' => 'Check Payment',
            'other' => 'Payment',
        ];

        $label = $paymentMethodLabels[$paymentMethod] ?? 'Payment';

        return "{$label} - {$expense->expense_number}";
    }

    /**
     * Post expense and create journal entry
     *
     * @param Expense $expense
     * @return array ['success' => bool, 'message' => string, 'journal_entry' => ?JournalEntry]
     */
    public function postExpense(Expense $expense): array
    {
        if (!$expense->canPost()) {
            return [
                'success' => false,
                'message' => 'Expense cannot be posted.',
                'journal_entry' => null,
            ];
        }

        return DB::transaction(function () use ($expense) {
            // Update expense status to posted
            $expense->status = Expense::STATUS_POSTED;
            $expense->save();

            // Create journal entry
            $journalEntry = $this->createFromExpense($expense);

            $message = 'Expense posted successfully.';
            if ($journalEntry) {
                $message .= ' Journal entry ' . $journalEntry->entry_number . ' created.';
            } elseif (!FinancialSetting::isEnabled('auto_journal_expense')) {
                $message .= ' Auto-journaling is disabled.';
            }

            return [
                'success' => true,
                'message' => $message,
                'journal_entry' => $journalEntry,
            ];
        });
    }

    /**
     * Reverse expense posting (void journal and revert status)
     *
     * @param Expense $expense
     * @param string $reason
     * @return array
     */
    public function reverseExpensePosting(Expense $expense, string $reason): array
    {
        if ($expense->status !== Expense::STATUS_POSTED) {
            return [
                'success' => false,
                'message' => 'Only posted expenses can be reversed.',
            ];
        }

        return DB::transaction(function () use ($expense, $reason) {
            // Void linked journal entry if exists
            if ($expense->journal_entry_id) {
                $journalEntry = JournalEntry::find($expense->journal_entry_id);
                if ($journalEntry && $journalEntry->canVoid()) {
                    $this->voidJournalEntry($journalEntry, 'Expense reversed: ' . $reason);
                }
            }

            // Revert expense status to approved
            $expense->status = Expense::STATUS_APPROVED;
            $expense->journal_entry_id = null;
            $expense->save();

            return [
                'success' => true,
                'message' => 'Expense posting reversed successfully.',
            ];
        });
    }
}

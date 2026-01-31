<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\FiscalPeriod;
use App\Models\JournalEntryLine;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinancialReportService
{
    /**
     * Get Trial Balance report
     *
     * @param int|null $periodId
     * @param string|null $asOfDate
     * @return array
     */
    public function getTrialBalance(?int $periodId = null, ?string $asOfDate = null): array
    {
        // Determine date range
        if ($periodId) {
            $period = FiscalPeriod::findOrFail($periodId);
            $startDate = null; // From beginning
            $endDate = $period->end_date;
        } else {
            $startDate = null;
            $endDate = $asOfDate ? Carbon::parse($asOfDate) : now();
        }

        // Get all active accounts
        $accounts = ChartOfAccount::where('is_active', true)
            ->where('is_header', false)
            ->orderBy('account_code')
            ->get();

        $trialBalance = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($accounts as $account) {
            // Get balance from journal entries
            $query = JournalEntryLine::where('account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($endDate) {
                    $q->where('status', 'posted');
                    if ($endDate) {
                        $q->where('entry_date', '<=', $endDate);
                    }
                });

            $debits = (clone $query)->sum('debit_amount');
            $credits = (clone $query)->sum('credit_amount');

            // Calculate balance based on normal balance
            $netBalance = $debits - $credits;

            // Skip accounts with no activity
            if ($debits == 0 && $credits == 0) {
                continue;
            }

            // For trial balance, show debit or credit balance
            $debitBalance = 0;
            $creditBalance = 0;

            if ($account->normal_balance === 'debit') {
                if ($netBalance >= 0) {
                    $debitBalance = $netBalance;
                } else {
                    $creditBalance = abs($netBalance);
                }
            } else {
                if ($netBalance <= 0) {
                    $creditBalance = abs($netBalance);
                } else {
                    $debitBalance = $netBalance;
                }
            }

            $trialBalance[] = [
                'account_id' => $account->id,
                'account_code' => $account->account_code,
                'account_name' => $account->account_name,
                'account_type' => $account->account_type,
                'debit_balance' => $debitBalance,
                'credit_balance' => $creditBalance,
            ];

            $totalDebit += $debitBalance;
            $totalCredit += $creditBalance;
        }

        return [
            'as_of_date' => $endDate ? $endDate->format('Y-m-d') : now()->format('Y-m-d'),
            'period' => $period ?? null,
            'accounts' => $trialBalance,
            'totals' => [
                'debit' => $totalDebit,
                'credit' => $totalCredit,
                'is_balanced' => abs($totalDebit - $totalCredit) < 0.01,
            ],
        ];
    }

    /**
     * Get Profit & Loss (Income Statement) report
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getProfitAndLoss(string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // Revenue accounts (4xxx)
        $revenueAccounts = $this->getAccountBalances(['revenue'], $start, $end);
        $totalRevenue = $revenueAccounts->sum('balance');

        // COGS accounts (5xxx)
        $cogsAccounts = $this->getAccountBalances(['cogs'], $start, $end);
        $totalCogs = $cogsAccounts->sum('balance');

        // Gross Profit
        $grossProfit = $totalRevenue - $totalCogs;

        // Operating Expenses (6xxx)
        $expenseAccounts = $this->getAccountBalances(['expense'], $start, $end);
        $totalExpenses = $expenseAccounts->sum('balance');

        // Operating Income
        $operatingIncome = $grossProfit - $totalExpenses;

        // Other Income (7xxx)
        $otherIncomeAccounts = $this->getAccountBalances(['other_income'], $start, $end);
        $totalOtherIncome = $otherIncomeAccounts->sum('balance');

        // Other Expenses (8xxx)
        $otherExpenseAccounts = $this->getAccountBalances(['other_expense'], $start, $end);
        $totalOtherExpenses = $otherExpenseAccounts->sum('balance');

        // Net Income
        $netIncome = $operatingIncome + $totalOtherIncome - $totalOtherExpenses;

        return [
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'revenue' => [
                'accounts' => $revenueAccounts->toArray(),
                'total' => $totalRevenue,
            ],
            'cogs' => [
                'accounts' => $cogsAccounts->toArray(),
                'total' => $totalCogs,
            ],
            'gross_profit' => $grossProfit,
            'expenses' => [
                'accounts' => $expenseAccounts->toArray(),
                'total' => $totalExpenses,
            ],
            'operating_income' => $operatingIncome,
            'other_income' => [
                'accounts' => $otherIncomeAccounts->toArray(),
                'total' => $totalOtherIncome,
            ],
            'other_expenses' => [
                'accounts' => $otherExpenseAccounts->toArray(),
                'total' => $totalOtherExpenses,
            ],
            'net_income' => $netIncome,
        ];
    }

    /**
     * Get Balance Sheet report
     *
     * @param string|null $asOfDate
     * @return array
     */
    public function getBalanceSheet(?string $asOfDate = null): array
    {
        $date = $asOfDate ? Carbon::parse($asOfDate)->endOfDay() : now()->endOfDay();

        // Assets (1xxx)
        $assetAccounts = $this->getAccountBalancesAsOf(['asset'], $date);
        $totalAssets = $assetAccounts->sum('balance');

        // Liabilities (2xxx)
        $liabilityAccounts = $this->getAccountBalancesAsOf(['liability'], $date);
        $totalLiabilities = $liabilityAccounts->sum('balance');

        // Equity (3xxx) - includes retained earnings
        $equityAccounts = $this->getAccountBalancesAsOf(['equity'], $date);
        $totalEquity = $equityAccounts->sum('balance');

        // Calculate current year net income
        $currentYearStart = $date->copy()->startOfYear();
        $currentYearPL = $this->getProfitAndLoss(
            $currentYearStart->format('Y-m-d'),
            $date->format('Y-m-d')
        );
        $currentYearNetIncome = $currentYearPL['net_income'];

        // Total equity including current year income
        $totalEquityWithIncome = $totalEquity + $currentYearNetIncome;

        return [
            'as_of_date' => $date->format('Y-m-d'),
            'assets' => [
                'accounts' => $this->groupByParent($assetAccounts),
                'total' => $totalAssets,
            ],
            'liabilities' => [
                'accounts' => $this->groupByParent($liabilityAccounts),
                'total' => $totalLiabilities,
            ],
            'equity' => [
                'accounts' => $this->groupByParent($equityAccounts),
                'current_year_net_income' => $currentYearNetIncome,
                'total' => $totalEquityWithIncome,
            ],
            'totals' => [
                'total_liabilities_equity' => $totalLiabilities + $totalEquityWithIncome,
                'is_balanced' => abs($totalAssets - ($totalLiabilities + $totalEquityWithIncome)) < 0.01,
            ],
        ];
    }

    /**
     * Get account balances for a date range (P&L style)
     */
    protected function getAccountBalances(array $accountTypes, Carbon $startDate, Carbon $endDate): Collection
    {
        $accounts = ChartOfAccount::whereIn('account_type', $accountTypes)
            ->where('is_active', true)
            ->where('is_header', false)
            ->orderBy('account_code')
            ->get();

        return $accounts->map(function ($account) use ($startDate, $endDate) {
            $query = JournalEntryLine::where('account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                    $q->where('status', 'posted')
                        ->whereBetween('entry_date', [$startDate, $endDate]);
                });

            $debits = (clone $query)->sum('debit_amount');
            $credits = (clone $query)->sum('credit_amount');

            // Calculate balance based on account type
            // Revenue/Liability/Equity: Credit increases, Debit decreases
            // Asset/Expense/COGS: Debit increases, Credit decreases
            if (in_array($account->account_type, ['revenue', 'other_income'])) {
                $balance = $credits - $debits;
            } else {
                $balance = $debits - $credits;
            }

            return [
                'account_id' => $account->id,
                'account_code' => $account->account_code,
                'account_name' => $account->account_name,
                'parent_id' => $account->parent_id,
                'balance' => abs($balance),
            ];
        })->filter(fn($item) => $item['balance'] != 0);
    }

    /**
     * Get account balances as of a specific date (Balance Sheet style)
     */
    protected function getAccountBalancesAsOf(array $accountTypes, Carbon $asOfDate): Collection
    {
        $accounts = ChartOfAccount::whereIn('account_type', $accountTypes)
            ->where('is_active', true)
            ->where('is_header', false)
            ->orderBy('account_code')
            ->get();

        return $accounts->map(function ($account) use ($asOfDate) {
            $query = JournalEntryLine::where('account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($asOfDate) {
                    $q->where('status', 'posted')
                        ->where('entry_date', '<=', $asOfDate);
                });

            $debits = (clone $query)->sum('debit_amount');
            $credits = (clone $query)->sum('credit_amount');

            // Calculate balance based on normal balance
            if ($account->normal_balance === 'debit') {
                $balance = $debits - $credits;
            } else {
                $balance = $credits - $debits;
            }

            return [
                'account_id' => $account->id,
                'account_code' => $account->account_code,
                'account_name' => $account->account_name,
                'parent_id' => $account->parent_id,
                'balance' => $balance,
            ];
        })->filter(fn($item) => $item['balance'] != 0);
    }

    /**
     * Group accounts by parent for hierarchical display
     */
    protected function groupByParent(Collection $accounts): array
    {
        // Get parent accounts
        $parentIds = $accounts->pluck('parent_id')->filter()->unique();
        $parents = ChartOfAccount::whereIn('id', $parentIds)->get()->keyBy('id');

        $grouped = [];

        // Group by parent
        foreach ($accounts as $account) {
            $parentId = $account['parent_id'];
            $parentName = $parentId && isset($parents[$parentId])
                ? $parents[$parentId]->account_name
                : 'Other';

            if (!isset($grouped[$parentName])) {
                $grouped[$parentName] = [
                    'name' => $parentName,
                    'accounts' => [],
                    'total' => 0,
                ];
            }

            $grouped[$parentName]['accounts'][] = $account;
            $grouped[$parentName]['total'] += $account['balance'];
        }

        return array_values($grouped);
    }

    /**
     * Get comparative P&L (compare two periods)
     */
    public function getComparativeProfitAndLoss(
        string $currentStart,
        string $currentEnd,
        string $previousStart,
        string $previousEnd
    ): array {
        $current = $this->getProfitAndLoss($currentStart, $currentEnd);
        $previous = $this->getProfitAndLoss($previousStart, $previousEnd);

        return [
            'current' => $current,
            'previous' => $previous,
            'variance' => [
                'revenue' => $current['revenue']['total'] - $previous['revenue']['total'],
                'gross_profit' => $current['gross_profit'] - $previous['gross_profit'],
                'operating_income' => $current['operating_income'] - $previous['operating_income'],
                'net_income' => $current['net_income'] - $previous['net_income'],
            ],
        ];
    }
}

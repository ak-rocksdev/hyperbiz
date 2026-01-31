<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks to allow truncation
        Schema::disableForeignKeyConstraints();

        // Clear related tables first if they exist
        if (Schema::hasTable('mst_expense_categories')) {
            DB::table('mst_expense_categories')->update(['default_account_id' => null]);
        }

        // Clear existing accounts if any
        ChartOfAccount::truncate();

        // Re-enable foreign key checks
        Schema::enableForeignKeyConstraints();

        $accounts = $this->getAccountsData();
        $accountIds = [];

        foreach ($accounts as $account) {
            $parentId = null;
            if ($account['parent_code']) {
                $parentId = $accountIds[$account['parent_code']] ?? null;
            }

            $created = ChartOfAccount::create([
                'account_code' => $account['code'],
                'account_name' => $account['name'],
                'account_type' => $account['type'],
                'normal_balance' => $account['normal_balance'],
                'parent_id' => $parentId,
                'level' => $account['level'],
                'is_header' => $account['is_header'],
                'is_bank_account' => $account['is_bank'] ?? false,
                'is_system' => $account['is_system'] ?? false,
                'is_active' => true,
            ]);

            $accountIds[$account['code']] = $created->id;
        }
    }

    /**
     * Get standard chart of accounts data
     */
    private function getAccountsData(): array
    {
        return [
            // ==================== ASSETS (1xxx) ====================
            ['code' => '1000', 'name' => 'Assets', 'type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => null, 'level' => 1, 'is_header' => true, 'is_system' => true],

            // Current Assets (11xx)
            ['code' => '1100', 'name' => 'Current Assets', 'type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1000', 'level' => 2, 'is_header' => true],
            ['code' => '1110', 'name' => 'Cash on Hand', 'type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1100', 'level' => 3, 'is_header' => false],
            ['code' => '1111', 'name' => 'Petty Cash', 'type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1110', 'level' => 4, 'is_header' => false],
            ['code' => '1120', 'name' => 'Cash in Bank', 'type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1100', 'level' => 3, 'is_header' => true],
            ['code' => '1121', 'name' => 'Bank - Operating Account', 'type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1120', 'level' => 4, 'is_header' => false, 'is_bank' => true],
            ['code' => '1122', 'name' => 'Bank - Savings Account', 'type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1120', 'level' => 4, 'is_header' => false, 'is_bank' => true],
            ['code' => '1130', 'name' => 'Accounts Receivable', 'type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1100', 'level' => 3, 'is_header' => true, 'is_system' => true],
            ['code' => '1131', 'name' => 'Trade Receivables', 'type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1130', 'level' => 4, 'is_header' => false],
            ['code' => '1135', 'name' => 'Allowance for Doubtful Accounts', 'type' => 'asset', 'normal_balance' => 'credit', 'parent_code' => '1130', 'level' => 4, 'is_header' => false],
            ['code' => '1140', 'name' => 'Inventory', 'type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1100', 'level' => 3, 'is_header' => true, 'is_system' => true],
            ['code' => '1141', 'name' => 'Merchandise Inventory', 'type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1140', 'level' => 4, 'is_header' => false],
            ['code' => '1142', 'name' => 'Raw Materials', 'type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1140', 'level' => 4, 'is_header' => false],
            ['code' => '1150', 'name' => 'Prepaid Expenses', 'type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1100', 'level' => 3, 'is_header' => true],
            ['code' => '1151', 'name' => 'Prepaid Insurance', 'type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1150', 'level' => 4, 'is_header' => false],
            ['code' => '1152', 'name' => 'Prepaid Rent', 'type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1150', 'level' => 4, 'is_header' => false],
            ['code' => '1160', 'name' => 'Tax Receivables', 'type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1100', 'level' => 3, 'is_header' => true],
            ['code' => '1161', 'name' => 'PPN Input (VAT Receivable)', 'type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1160', 'level' => 4, 'is_header' => false],
            ['code' => '1162', 'name' => 'Prepaid Tax (PPh)', 'type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1160', 'level' => 4, 'is_header' => false],

            // Fixed Assets (12xx)
            ['code' => '1200', 'name' => 'Fixed Assets', 'type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1000', 'level' => 2, 'is_header' => true],
            ['code' => '1210', 'name' => 'Land', 'type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1200', 'level' => 3, 'is_header' => false],
            ['code' => '1220', 'name' => 'Buildings', 'type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1200', 'level' => 3, 'is_header' => false],
            ['code' => '1221', 'name' => 'Accumulated Depreciation - Buildings', 'type' => 'asset', 'normal_balance' => 'credit', 'parent_code' => '1220', 'level' => 4, 'is_header' => false],
            ['code' => '1230', 'name' => 'Equipment', 'type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1200', 'level' => 3, 'is_header' => false],
            ['code' => '1231', 'name' => 'Accumulated Depreciation - Equipment', 'type' => 'asset', 'normal_balance' => 'credit', 'parent_code' => '1230', 'level' => 4, 'is_header' => false],
            ['code' => '1240', 'name' => 'Vehicles', 'type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1200', 'level' => 3, 'is_header' => false],
            ['code' => '1241', 'name' => 'Accumulated Depreciation - Vehicles', 'type' => 'asset', 'normal_balance' => 'credit', 'parent_code' => '1240', 'level' => 4, 'is_header' => false],
            ['code' => '1250', 'name' => 'Furniture & Fixtures', 'type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1200', 'level' => 3, 'is_header' => false],
            ['code' => '1251', 'name' => 'Accumulated Depreciation - Furniture', 'type' => 'asset', 'normal_balance' => 'credit', 'parent_code' => '1250', 'level' => 4, 'is_header' => false],

            // ==================== LIABILITIES (2xxx) ====================
            ['code' => '2000', 'name' => 'Liabilities', 'type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => null, 'level' => 1, 'is_header' => true, 'is_system' => true],

            // Current Liabilities (21xx)
            ['code' => '2100', 'name' => 'Current Liabilities', 'type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2000', 'level' => 2, 'is_header' => true],
            ['code' => '2110', 'name' => 'Accounts Payable', 'type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2100', 'level' => 3, 'is_header' => true, 'is_system' => true],
            ['code' => '2111', 'name' => 'Trade Payables', 'type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2110', 'level' => 4, 'is_header' => false],
            ['code' => '2120', 'name' => 'Accrued Expenses', 'type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2100', 'level' => 3, 'is_header' => true],
            ['code' => '2121', 'name' => 'Accrued Salaries', 'type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2120', 'level' => 4, 'is_header' => false],
            ['code' => '2122', 'name' => 'Accrued Utilities', 'type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2120', 'level' => 4, 'is_header' => false],
            ['code' => '2130', 'name' => 'Taxes Payable', 'type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2100', 'level' => 3, 'is_header' => true],
            ['code' => '2131', 'name' => 'PPN Output (VAT Payable)', 'type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2130', 'level' => 4, 'is_header' => false],
            ['code' => '2132', 'name' => 'PPh 21 Payable', 'type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2130', 'level' => 4, 'is_header' => false],
            ['code' => '2133', 'name' => 'PPh 23 Payable', 'type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2130', 'level' => 4, 'is_header' => false],
            ['code' => '2134', 'name' => 'PPh 4(2) Payable', 'type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2130', 'level' => 4, 'is_header' => false],
            ['code' => '2135', 'name' => 'Income Tax Payable', 'type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2130', 'level' => 4, 'is_header' => false],
            ['code' => '2140', 'name' => 'Customer Deposits', 'type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2100', 'level' => 3, 'is_header' => false],
            ['code' => '2150', 'name' => 'Unearned Revenue', 'type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2100', 'level' => 3, 'is_header' => false],
            ['code' => '2160', 'name' => 'Short-Term Loans', 'type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2100', 'level' => 3, 'is_header' => false],

            // Long-Term Liabilities (22xx)
            ['code' => '2200', 'name' => 'Long-Term Liabilities', 'type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2000', 'level' => 2, 'is_header' => true],
            ['code' => '2210', 'name' => 'Long-Term Loans', 'type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2200', 'level' => 3, 'is_header' => false],
            ['code' => '2220', 'name' => 'Mortgage Payable', 'type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2200', 'level' => 3, 'is_header' => false],

            // ==================== EQUITY (3xxx) ====================
            ['code' => '3000', 'name' => 'Equity', 'type' => 'equity', 'normal_balance' => 'credit', 'parent_code' => null, 'level' => 1, 'is_header' => true, 'is_system' => true],
            ['code' => '3100', 'name' => 'Owner\'s Capital', 'type' => 'equity', 'normal_balance' => 'credit', 'parent_code' => '3000', 'level' => 2, 'is_header' => true],
            ['code' => '3110', 'name' => 'Paid-in Capital', 'type' => 'equity', 'normal_balance' => 'credit', 'parent_code' => '3100', 'level' => 3, 'is_header' => false],
            ['code' => '3120', 'name' => 'Additional Paid-in Capital', 'type' => 'equity', 'normal_balance' => 'credit', 'parent_code' => '3100', 'level' => 3, 'is_header' => false],
            ['code' => '3200', 'name' => 'Retained Earnings', 'type' => 'equity', 'normal_balance' => 'credit', 'parent_code' => '3000', 'level' => 2, 'is_header' => true, 'is_system' => true],
            ['code' => '3210', 'name' => 'Retained Earnings - Prior Years', 'type' => 'equity', 'normal_balance' => 'credit', 'parent_code' => '3200', 'level' => 3, 'is_header' => false],
            ['code' => '3220', 'name' => 'Current Year Earnings', 'type' => 'equity', 'normal_balance' => 'credit', 'parent_code' => '3200', 'level' => 3, 'is_header' => false],
            ['code' => '3300', 'name' => 'Owner\'s Drawings', 'type' => 'equity', 'normal_balance' => 'debit', 'parent_code' => '3000', 'level' => 2, 'is_header' => false],

            // ==================== REVENUE (4xxx) ====================
            ['code' => '4000', 'name' => 'Revenue', 'type' => 'revenue', 'normal_balance' => 'credit', 'parent_code' => null, 'level' => 1, 'is_header' => true, 'is_system' => true],
            ['code' => '4100', 'name' => 'Sales Revenue', 'type' => 'revenue', 'normal_balance' => 'credit', 'parent_code' => '4000', 'level' => 2, 'is_header' => true],
            ['code' => '4110', 'name' => 'Product Sales', 'type' => 'revenue', 'normal_balance' => 'credit', 'parent_code' => '4100', 'level' => 3, 'is_header' => false, 'is_system' => true],
            ['code' => '4120', 'name' => 'Service Revenue', 'type' => 'revenue', 'normal_balance' => 'credit', 'parent_code' => '4100', 'level' => 3, 'is_header' => false],
            ['code' => '4130', 'name' => 'Sales Returns & Allowances', 'type' => 'revenue', 'normal_balance' => 'debit', 'parent_code' => '4100', 'level' => 3, 'is_header' => false],
            ['code' => '4140', 'name' => 'Sales Discounts', 'type' => 'revenue', 'normal_balance' => 'debit', 'parent_code' => '4100', 'level' => 3, 'is_header' => false],
            ['code' => '4150', 'name' => 'Shipping Revenue', 'type' => 'revenue', 'normal_balance' => 'credit', 'parent_code' => '4100', 'level' => 3, 'is_header' => false],

            // ==================== COST OF GOODS SOLD (5xxx) ====================
            ['code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => 'cogs', 'normal_balance' => 'debit', 'parent_code' => null, 'level' => 1, 'is_header' => true, 'is_system' => true],
            ['code' => '5100', 'name' => 'Cost of Sales', 'type' => 'cogs', 'normal_balance' => 'debit', 'parent_code' => '5000', 'level' => 2, 'is_header' => true],
            ['code' => '5110', 'name' => 'Cost of Merchandise Sold', 'type' => 'cogs', 'normal_balance' => 'debit', 'parent_code' => '5100', 'level' => 3, 'is_header' => false, 'is_system' => true],
            ['code' => '5120', 'name' => 'Freight-In', 'type' => 'cogs', 'normal_balance' => 'debit', 'parent_code' => '5100', 'level' => 3, 'is_header' => false],
            ['code' => '5130', 'name' => 'Purchase Returns & Allowances', 'type' => 'cogs', 'normal_balance' => 'credit', 'parent_code' => '5100', 'level' => 3, 'is_header' => false],
            ['code' => '5140', 'name' => 'Purchase Discounts', 'type' => 'cogs', 'normal_balance' => 'credit', 'parent_code' => '5100', 'level' => 3, 'is_header' => false],
            ['code' => '5150', 'name' => 'Inventory Adjustments', 'type' => 'cogs', 'normal_balance' => 'debit', 'parent_code' => '5100', 'level' => 3, 'is_header' => false],

            // ==================== OPERATING EXPENSES (6xxx) ====================
            ['code' => '6000', 'name' => 'Operating Expenses', 'type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => null, 'level' => 1, 'is_header' => true, 'is_system' => true],

            // Selling Expenses (61xx)
            ['code' => '6100', 'name' => 'Selling Expenses', 'type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => '6000', 'level' => 2, 'is_header' => true],
            ['code' => '6110', 'name' => 'Advertising & Marketing', 'type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => '6100', 'level' => 3, 'is_header' => false],
            ['code' => '6120', 'name' => 'Sales Commissions', 'type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => '6100', 'level' => 3, 'is_header' => false],
            ['code' => '6130', 'name' => 'Shipping & Delivery', 'type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => '6100', 'level' => 3, 'is_header' => false],
            ['code' => '6140', 'name' => 'Travel Expenses - Sales', 'type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => '6100', 'level' => 3, 'is_header' => false],

            // Administrative Expenses (62xx)
            ['code' => '6200', 'name' => 'Administrative Expenses', 'type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => '6000', 'level' => 2, 'is_header' => true],
            ['code' => '6210', 'name' => 'Salaries & Wages', 'type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => '6200', 'level' => 3, 'is_header' => true],
            ['code' => '6211', 'name' => 'Salaries - Management', 'type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => '6210', 'level' => 4, 'is_header' => false],
            ['code' => '6212', 'name' => 'Salaries - Staff', 'type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => '6210', 'level' => 4, 'is_header' => false],
            ['code' => '6213', 'name' => 'Overtime Pay', 'type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => '6210', 'level' => 4, 'is_header' => false],
            ['code' => '6220', 'name' => 'Employee Benefits', 'type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => '6200', 'level' => 3, 'is_header' => true],
            ['code' => '6221', 'name' => 'Health Insurance (BPJS Kesehatan)', 'type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => '6220', 'level' => 4, 'is_header' => false],
            ['code' => '6222', 'name' => 'Pension (BPJS Ketenagakerjaan)', 'type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => '6220', 'level' => 4, 'is_header' => false],
            ['code' => '6223', 'name' => 'THR (Holiday Allowance)', 'type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => '6220', 'level' => 4, 'is_header' => false],
            ['code' => '6230', 'name' => 'Rent Expense', 'type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => '6200', 'level' => 3, 'is_header' => false],
            ['code' => '6240', 'name' => 'Utilities', 'type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => '6200', 'level' => 3, 'is_header' => true],
            ['code' => '6241', 'name' => 'Electricity (PLN)', 'type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => '6240', 'level' => 4, 'is_header' => false],
            ['code' => '6242', 'name' => 'Water (PDAM)', 'type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => '6240', 'level' => 4, 'is_header' => false],
            ['code' => '6243', 'name' => 'Internet & Phone', 'type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => '6240', 'level' => 4, 'is_header' => false],
            ['code' => '6250', 'name' => 'Office Supplies', 'type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => '6200', 'level' => 3, 'is_header' => false],
            ['code' => '6260', 'name' => 'Insurance', 'type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => '6200', 'level' => 3, 'is_header' => false],
            ['code' => '6270', 'name' => 'Professional Fees', 'type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => '6200', 'level' => 3, 'is_header' => true],
            ['code' => '6271', 'name' => 'Legal Fees', 'type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => '6270', 'level' => 4, 'is_header' => false],
            ['code' => '6272', 'name' => 'Accounting Fees', 'type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => '6270', 'level' => 4, 'is_header' => false],
            ['code' => '6273', 'name' => 'Consulting Fees', 'type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => '6270', 'level' => 4, 'is_header' => false],
            ['code' => '6280', 'name' => 'Depreciation Expense', 'type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => '6200', 'level' => 3, 'is_header' => false],
            ['code' => '6290', 'name' => 'Repairs & Maintenance', 'type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => '6200', 'level' => 3, 'is_header' => false],

            // ==================== OTHER INCOME (7xxx) ====================
            ['code' => '7000', 'name' => 'Other Income', 'type' => 'other_income', 'normal_balance' => 'credit', 'parent_code' => null, 'level' => 1, 'is_header' => true, 'is_system' => true],
            ['code' => '7100', 'name' => 'Interest Income', 'type' => 'other_income', 'normal_balance' => 'credit', 'parent_code' => '7000', 'level' => 2, 'is_header' => false],
            ['code' => '7200', 'name' => 'Dividend Income', 'type' => 'other_income', 'normal_balance' => 'credit', 'parent_code' => '7000', 'level' => 2, 'is_header' => false],
            ['code' => '7300', 'name' => 'Gain on Asset Sale', 'type' => 'other_income', 'normal_balance' => 'credit', 'parent_code' => '7000', 'level' => 2, 'is_header' => false],
            ['code' => '7400', 'name' => 'Foreign Exchange Gain', 'type' => 'other_income', 'normal_balance' => 'credit', 'parent_code' => '7000', 'level' => 2, 'is_header' => false],
            ['code' => '7500', 'name' => 'Miscellaneous Income', 'type' => 'other_income', 'normal_balance' => 'credit', 'parent_code' => '7000', 'level' => 2, 'is_header' => false],

            // ==================== OTHER EXPENSES (8xxx) ====================
            ['code' => '8000', 'name' => 'Other Expenses', 'type' => 'other_expense', 'normal_balance' => 'debit', 'parent_code' => null, 'level' => 1, 'is_header' => true, 'is_system' => true],
            ['code' => '8100', 'name' => 'Interest Expense', 'type' => 'other_expense', 'normal_balance' => 'debit', 'parent_code' => '8000', 'level' => 2, 'is_header' => false],
            ['code' => '8200', 'name' => 'Bank Charges', 'type' => 'other_expense', 'normal_balance' => 'debit', 'parent_code' => '8000', 'level' => 2, 'is_header' => false],
            ['code' => '8300', 'name' => 'Loss on Asset Sale', 'type' => 'other_expense', 'normal_balance' => 'debit', 'parent_code' => '8000', 'level' => 2, 'is_header' => false],
            ['code' => '8400', 'name' => 'Foreign Exchange Loss', 'type' => 'other_expense', 'normal_balance' => 'debit', 'parent_code' => '8000', 'level' => 2, 'is_header' => false],
            ['code' => '8500', 'name' => 'Bad Debt Expense', 'type' => 'other_expense', 'normal_balance' => 'debit', 'parent_code' => '8000', 'level' => 2, 'is_header' => false],
            ['code' => '8600', 'name' => 'Penalties & Fines', 'type' => 'other_expense', 'normal_balance' => 'debit', 'parent_code' => '8000', 'level' => 2, 'is_header' => false],
        ];
    }
}

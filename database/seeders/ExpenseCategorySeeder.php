<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get expense accounts for default mapping
        $accounts = ChartOfAccount::whereIn('account_code', [
            '6210', // Salaries & Wages
            '6220', // Office Supplies
            '6230', // Rent Expense
            '6240', // Utilities
            '6250', // Communication
            '6260', // Insurance
            '6270', // Travel & Entertainment
            '6280', // Professional Fees
            '6290', // Marketing & Advertising
            '6310', // Maintenance & Repairs
            '6320', // Depreciation Expense
            '6330', // Bank Charges
            '6340', // Licenses & Permits
            '6350', // Miscellaneous Expense
        ])->pluck('id', 'account_code');

        // Define expense categories
        $categories = [
            // Personnel
            [
                'code' => 'EXP-PERS',
                'name' => 'Personnel',
                'description' => 'Employee-related expenses',
                'children' => [
                    ['code' => 'EXP-SAL', 'name' => 'Salaries & Wages', 'account' => '6210', 'description' => 'Regular employee salaries and wages'],
                    ['code' => 'EXP-BEN', 'name' => 'Benefits', 'account' => '6210', 'description' => 'Employee benefits and insurance'],
                    ['code' => 'EXP-BON', 'name' => 'Bonuses', 'account' => '6210', 'description' => 'Performance bonuses and incentives'],
                    ['code' => 'EXP-TRN', 'name' => 'Training', 'account' => '6210', 'description' => 'Employee training and development'],
                ],
            ],
            // Office
            [
                'code' => 'EXP-OFF',
                'name' => 'Office',
                'description' => 'Office operations expenses',
                'children' => [
                    ['code' => 'EXP-SUP', 'name' => 'Office Supplies', 'account' => '6220', 'description' => 'Stationery, office supplies'],
                    ['code' => 'EXP-RNT', 'name' => 'Rent', 'account' => '6230', 'description' => 'Office rent and lease payments'],
                    ['code' => 'EXP-UTL', 'name' => 'Utilities', 'account' => '6240', 'description' => 'Electricity, water, gas'],
                    ['code' => 'EXP-COM', 'name' => 'Communication', 'account' => '6250', 'description' => 'Phone, internet, postage'],
                    ['code' => 'EXP-MTN', 'name' => 'Maintenance', 'account' => '6310', 'description' => 'Office maintenance and repairs'],
                ],
            ],
            // Operations
            [
                'code' => 'EXP-OPS',
                'name' => 'Operations',
                'description' => 'Business operations expenses',
                'children' => [
                    ['code' => 'EXP-TRV', 'name' => 'Travel', 'account' => '6270', 'description' => 'Business travel expenses'],
                    ['code' => 'EXP-ENT', 'name' => 'Entertainment', 'account' => '6270', 'description' => 'Client entertainment and meals'],
                    ['code' => 'EXP-VEH', 'name' => 'Vehicle', 'account' => '6310', 'description' => 'Vehicle fuel, maintenance, insurance'],
                    ['code' => 'EXP-SHP', 'name' => 'Shipping', 'account' => '6350', 'description' => 'Shipping and delivery costs'],
                ],
            ],
            // Professional Services
            [
                'code' => 'EXP-PRO',
                'name' => 'Professional Services',
                'description' => 'External professional services',
                'children' => [
                    ['code' => 'EXP-LEG', 'name' => 'Legal', 'account' => '6280', 'description' => 'Legal fees and services'],
                    ['code' => 'EXP-ACC', 'name' => 'Accounting', 'account' => '6280', 'description' => 'Accounting and audit fees'],
                    ['code' => 'EXP-CON', 'name' => 'Consulting', 'account' => '6280', 'description' => 'Consulting and advisory services'],
                    ['code' => 'EXP-IT', 'name' => 'IT Services', 'account' => '6280', 'description' => 'IT support and software services'],
                ],
            ],
            // Marketing
            [
                'code' => 'EXP-MKT',
                'name' => 'Marketing',
                'description' => 'Marketing and advertising expenses',
                'children' => [
                    ['code' => 'EXP-ADV', 'name' => 'Advertising', 'account' => '6290', 'description' => 'Online and offline advertising'],
                    ['code' => 'EXP-PRM', 'name' => 'Promotions', 'account' => '6290', 'description' => 'Promotional materials and events'],
                    ['code' => 'EXP-BRD', 'name' => 'Branding', 'account' => '6290', 'description' => 'Branding and design services'],
                ],
            ],
            // Financial
            [
                'code' => 'EXP-FIN',
                'name' => 'Financial',
                'description' => 'Financial charges and fees',
                'children' => [
                    ['code' => 'EXP-BNK', 'name' => 'Bank Charges', 'account' => '6330', 'description' => 'Bank fees and charges'],
                    ['code' => 'EXP-INS', 'name' => 'Insurance', 'account' => '6260', 'description' => 'Business insurance premiums'],
                    ['code' => 'EXP-INT', 'name' => 'Interest', 'account' => '6330', 'description' => 'Interest on loans and credit'],
                ],
            ],
            // Regulatory
            [
                'code' => 'EXP-REG',
                'name' => 'Regulatory',
                'description' => 'Licenses, permits, and compliance',
                'children' => [
                    ['code' => 'EXP-LIC', 'name' => 'Licenses & Permits', 'account' => '6340', 'description' => 'Business licenses and permits'],
                    ['code' => 'EXP-TAX', 'name' => 'Taxes & Duties', 'account' => '6340', 'description' => 'Non-income taxes and duties'],
                    ['code' => 'EXP-FEE', 'name' => 'Government Fees', 'account' => '6340', 'description' => 'Government registration fees'],
                ],
            ],
            // Other
            [
                'code' => 'EXP-OTH',
                'name' => 'Other',
                'description' => 'Miscellaneous expenses',
                'children' => [
                    ['code' => 'EXP-DEP', 'name' => 'Depreciation', 'account' => '6320', 'description' => 'Asset depreciation'],
                    ['code' => 'EXP-MSC', 'name' => 'Miscellaneous', 'account' => '6350', 'description' => 'Other uncategorized expenses'],
                ],
            ],
        ];

        foreach ($categories as $category) {
            $parent = ExpenseCategory::firstOrCreate(
                ['code' => $category['code']],
                [
                    'name' => $category['name'],
                    'description' => $category['description'] ?? null,
                    'is_active' => true,
                    'created_by' => 1,
                ]
            );

            // Create children
            if (isset($category['children'])) {
                foreach ($category['children'] as $child) {
                    ExpenseCategory::firstOrCreate(
                        ['code' => $child['code']],
                        [
                            'name' => $child['name'],
                            'parent_id' => $parent->id,
                            'default_account_id' => isset($child['account']) ? ($accounts[$child['account']] ?? null) : null,
                            'description' => $child['description'] ?? null,
                            'is_active' => true,
                            'created_by' => 1,
                        ]
                    );
                }
            }
        }
    }
}

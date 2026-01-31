<?php

namespace Database\Seeders;

use App\Models\FinancialSetting;
use Illuminate\Database\Seeder;

class FinancialSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // General Settings
            [
                'setting_key' => 'financial_module_enabled',
                'setting_value' => '1',
                'setting_type' => 'boolean',
                'setting_group' => 'general',
                'description' => 'Enable/disable the financial module',
                'is_system' => true,
            ],
            [
                'setting_key' => 'base_currency',
                'setting_value' => 'IDR',
                'setting_type' => 'string',
                'setting_group' => 'general',
                'description' => 'Base currency for financial reports',
                'is_system' => true,
            ],

            // Auto-Journal Settings
            [
                'setting_key' => 'auto_journal_enabled',
                'setting_value' => '0',
                'setting_type' => 'boolean',
                'setting_group' => 'journal',
                'description' => 'Master toggle for auto-journaling',
                'is_system' => false,
            ],
            [
                'setting_key' => 'auto_journal_sales',
                'setting_value' => '0',
                'setting_type' => 'boolean',
                'setting_group' => 'journal',
                'description' => 'Auto-create journal entry when sales order is delivered',
                'is_system' => false,
            ],
            [
                'setting_key' => 'auto_journal_purchase',
                'setting_value' => '0',
                'setting_type' => 'boolean',
                'setting_group' => 'journal',
                'description' => 'Auto-create journal entry when purchase order is received',
                'is_system' => false,
            ],
            [
                'setting_key' => 'auto_journal_payment',
                'setting_value' => '0',
                'setting_type' => 'boolean',
                'setting_group' => 'journal',
                'description' => 'Auto-create journal entry when payment is confirmed',
                'is_system' => false,
            ],
            [
                'setting_key' => 'auto_journal_expense',
                'setting_value' => '0',
                'setting_type' => 'boolean',
                'setting_group' => 'journal',
                'description' => 'Auto-create journal entry when expense is posted',
                'is_system' => false,
            ],

            // Tax Settings - PPN (VAT)
            [
                'setting_key' => 'tax_ppn_enabled',
                'setting_value' => '0',
                'setting_type' => 'boolean',
                'setting_group' => 'tax',
                'description' => 'Enable PPN (VAT) calculation',
                'is_system' => false,
            ],
            [
                'setting_key' => 'tax_ppn_rate',
                'setting_value' => '11',
                'setting_type' => 'decimal',
                'setting_group' => 'tax',
                'description' => 'PPN rate percentage (default 11%)',
                'is_system' => false,
            ],
            [
                'setting_key' => 'tax_ppn_inclusive',
                'setting_value' => '0',
                'setting_type' => 'boolean',
                'setting_group' => 'tax',
                'description' => 'Whether prices are PPN inclusive by default',
                'is_system' => false,
            ],

            // Tax Settings - PPh (Withholding Tax)
            [
                'setting_key' => 'tax_pph21_enabled',
                'setting_value' => '0',
                'setting_type' => 'boolean',
                'setting_group' => 'tax',
                'description' => 'Enable PPh 21 (Employee Income Tax) calculation',
                'is_system' => false,
            ],
            [
                'setting_key' => 'tax_pph23_enabled',
                'setting_value' => '0',
                'setting_type' => 'boolean',
                'setting_group' => 'tax',
                'description' => 'Enable PPh 23 (Withholding Tax on Services) calculation',
                'is_system' => false,
            ],
            [
                'setting_key' => 'tax_pph23_rate',
                'setting_value' => '2',
                'setting_type' => 'decimal',
                'setting_group' => 'tax',
                'description' => 'PPh 23 rate percentage (default 2%)',
                'is_system' => false,
            ],
            [
                'setting_key' => 'tax_pph4_enabled',
                'setting_value' => '0',
                'setting_type' => 'boolean',
                'setting_group' => 'tax',
                'description' => 'Enable PPh 4(2) (Final Withholding Tax) calculation',
                'is_system' => false,
            ],
            [
                'setting_key' => 'tax_pph4_rate',
                'setting_value' => '10',
                'setting_type' => 'decimal',
                'setting_group' => 'tax',
                'description' => 'PPh 4(2) rate percentage (default 10%)',
                'is_system' => false,
            ],

            // Faktur Pajak Settings
            [
                'setting_key' => 'faktur_pajak_enabled',
                'setting_value' => '0',
                'setting_type' => 'boolean',
                'setting_group' => 'tax',
                'description' => 'Enable Faktur Pajak (Tax Invoice) tracking',
                'is_system' => false,
            ],
            [
                'setting_key' => 'faktur_pajak_auto_number',
                'setting_value' => '0',
                'setting_type' => 'boolean',
                'setting_group' => 'tax',
                'description' => 'Auto-generate Faktur Pajak number',
                'is_system' => false,
            ],
            [
                'setting_key' => 'faktur_pajak_prefix',
                'setting_value' => '',
                'setting_type' => 'string',
                'setting_group' => 'tax',
                'description' => 'Faktur Pajak number prefix',
                'is_system' => false,
            ],
            [
                'setting_key' => 'company_npwp',
                'setting_value' => '',
                'setting_type' => 'string',
                'setting_group' => 'tax',
                'description' => 'Company NPWP for tax documents',
                'is_system' => false,
            ],

            // Default Account Mappings
            [
                'setting_key' => 'default_ar_account',
                'setting_value' => '',
                'setting_type' => 'integer',
                'setting_group' => 'accounts',
                'description' => 'Default Accounts Receivable GL account ID',
                'is_system' => false,
            ],
            [
                'setting_key' => 'default_ap_account',
                'setting_value' => '',
                'setting_type' => 'integer',
                'setting_group' => 'accounts',
                'description' => 'Default Accounts Payable GL account ID',
                'is_system' => false,
            ],
            [
                'setting_key' => 'default_sales_account',
                'setting_value' => '',
                'setting_type' => 'integer',
                'setting_group' => 'accounts',
                'description' => 'Default Sales Revenue GL account ID',
                'is_system' => false,
            ],
            [
                'setting_key' => 'default_cogs_account',
                'setting_value' => '',
                'setting_type' => 'integer',
                'setting_group' => 'accounts',
                'description' => 'Default Cost of Goods Sold GL account ID',
                'is_system' => false,
            ],
            [
                'setting_key' => 'default_inventory_account',
                'setting_value' => '',
                'setting_type' => 'integer',
                'setting_group' => 'accounts',
                'description' => 'Default Inventory GL account ID',
                'is_system' => false,
            ],
            [
                'setting_key' => 'default_cash_account',
                'setting_value' => '',
                'setting_type' => 'integer',
                'setting_group' => 'accounts',
                'description' => 'Default Cash GL account ID',
                'is_system' => false,
            ],
            [
                'setting_key' => 'default_bank_account',
                'setting_value' => '',
                'setting_type' => 'integer',
                'setting_group' => 'accounts',
                'description' => 'Default Bank GL account ID',
                'is_system' => false,
            ],
            [
                'setting_key' => 'default_ppn_input_account',
                'setting_value' => '',
                'setting_type' => 'integer',
                'setting_group' => 'accounts',
                'description' => 'Default PPN Input (VAT Receivable) GL account ID',
                'is_system' => false,
            ],
            [
                'setting_key' => 'default_ppn_output_account',
                'setting_value' => '',
                'setting_type' => 'integer',
                'setting_group' => 'accounts',
                'description' => 'Default PPN Output (VAT Payable) GL account ID',
                'is_system' => false,
            ],
            [
                'setting_key' => 'default_retained_earnings_account',
                'setting_value' => '',
                'setting_type' => 'integer',
                'setting_group' => 'accounts',
                'description' => 'Default Retained Earnings GL account ID',
                'is_system' => false,
            ],
        ];

        foreach ($settings as $setting) {
            FinancialSetting::updateOrCreate(
                ['setting_key' => $setting['setting_key']],
                $setting
            );
        }
    }
}

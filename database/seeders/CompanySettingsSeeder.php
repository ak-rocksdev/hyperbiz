<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Company;

class CompanySettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates default settings for all companies.
     */
    public function run(): void
    {
        $defaultSettings = [
            [
                'setting_key' => 'costing_method',
                'setting_value' => 'fifo',
                'description' => 'Inventory costing method: fifo, lifo, average, or none',
            ],
            [
                'setting_key' => 'default_currency',
                'setting_value' => 'IDR',
                'description' => 'Default currency code for transactions',
            ],
            [
                'setting_key' => 'default_tax_enabled',
                'setting_value' => 'false',
                'description' => 'Whether tax is enabled by default on new transactions',
            ],
            [
                'setting_key' => 'default_tax_name',
                'setting_value' => 'PPN',
                'description' => 'Default tax name (e.g., PPN, VAT)',
            ],
            [
                'setting_key' => 'default_tax_percentage',
                'setting_value' => '11',
                'description' => 'Default tax percentage',
            ],
            [
                'setting_key' => 'default_payment_terms',
                'setting_value' => 'Net 30',
                'description' => 'Default payment terms for transactions',
            ],
            [
                'setting_key' => 'po_number_prefix',
                'setting_value' => 'PO',
                'description' => 'Prefix for Purchase Order numbers',
            ],
            [
                'setting_key' => 'so_number_prefix',
                'setting_value' => 'SO',
                'description' => 'Prefix for Sales Order numbers',
            ],
            [
                'setting_key' => 'low_stock_alert_enabled',
                'setting_value' => 'true',
                'description' => 'Enable low stock alerts based on min_stock_level',
            ],
        ];

        // Get all companies
        $companies = Company::all();

        if ($companies->isEmpty()) {
            $this->command->warn('No companies found. Settings will be created when companies exist.');
            return;
        }

        $count = 0;
        foreach ($companies as $company) {
            foreach ($defaultSettings as $setting) {
                DB::table('company_settings')->updateOrInsert(
                    [
                        'company_id' => $company->id,
                        'setting_key' => $setting['setting_key'],
                    ],
                    [
                        'setting_value' => $setting['setting_value'],
                        'description' => $setting['description'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
                $count++;
            }
        }

        $this->command->info("Seeded {$count} company settings for {$companies->count()} companies.");
    }
}

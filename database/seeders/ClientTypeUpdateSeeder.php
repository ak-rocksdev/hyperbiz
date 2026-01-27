<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClientTypeUpdateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Updates existing client types with can_purchase and can_sell flags.
     */
    public function run(): void
    {
        $clientTypes = [
            [
                'client_type' => 'Supplier',
                'can_purchase' => true,
                'can_sell' => false,
                'description' => 'Suppliers provide goods/materials. Used for Purchase Orders.',
            ],
            [
                'client_type' => 'Manufacturer',
                'can_purchase' => true,
                'can_sell' => true,
                'description' => 'Manufacturers produce goods. Can be both supplier and customer.',
            ],
            [
                'client_type' => 'Importir',
                'can_purchase' => true,
                'can_sell' => false,
                'description' => 'Importers bring goods from overseas. Used for Purchase Orders.',
            ],
            [
                'client_type' => 'Distributor',
                'can_purchase' => true,
                'can_sell' => true,
                'description' => 'Distributors handle product distribution. Two-way trading common.',
            ],
            [
                'client_type' => 'Wholesaler',
                'can_purchase' => true,
                'can_sell' => true,
                'description' => 'Wholesalers buy/sell in bulk. Two-way trading possible.',
            ],
            [
                'client_type' => 'Agent',
                'can_purchase' => true,
                'can_sell' => true,
                'description' => 'Agents act as intermediaries. Can facilitate both directions.',
            ],
            [
                'client_type' => 'Retailer',
                'can_purchase' => false,
                'can_sell' => true,
                'description' => 'Retailers sell to end consumers. Used for Sales Orders.',
            ],
            [
                'client_type' => 'End Customer',
                'can_purchase' => false,
                'can_sell' => true,
                'description' => 'Final consumers. Used for Sales Orders only.',
            ],
        ];

        foreach ($clientTypes as $type) {
            // Try to update existing record
            $updated = DB::table('mst_client_type')
                ->where('client_type', $type['client_type'])
                ->update([
                    'can_purchase' => $type['can_purchase'],
                    'can_sell' => $type['can_sell'],
                    'description' => $type['description'],
                    'updated_at' => now(),
                ]);

            // If not exists, insert new record
            if ($updated === 0) {
                DB::table('mst_client_type')->insert([
                    'client_type' => $type['client_type'],
                    'can_purchase' => $type['can_purchase'],
                    'can_sell' => $type['can_sell'],
                    'description' => $type['description'],
                    'created_by' => 'system',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('Updated/seeded ' . count($clientTypes) . ' client types with transaction flags.');
    }
}

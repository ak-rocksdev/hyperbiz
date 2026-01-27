<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $uoms = [
            // Weight
            ['code' => 'KG', 'name' => 'Kilogram', 'description' => 'Unit of mass/weight'],
            ['code' => 'G', 'name' => 'Gram', 'description' => 'Unit of mass/weight (1/1000 kg)'],
            ['code' => 'TON', 'name' => 'Metric Ton', 'description' => 'Unit of mass (1000 kg)'],
            ['code' => 'LB', 'name' => 'Pound', 'description' => 'Unit of mass (imperial)'],

            // Count
            ['code' => 'PCS', 'name' => 'Pieces', 'description' => 'Individual items/units'],
            ['code' => 'UNIT', 'name' => 'Unit', 'description' => 'Single unit'],
            ['code' => 'SET', 'name' => 'Set', 'description' => 'Set/collection of items'],
            ['code' => 'PAIR', 'name' => 'Pair', 'description' => 'Two items together'],
            ['code' => 'DZN', 'name' => 'Dozen', 'description' => '12 pieces'],

            // Volume
            ['code' => 'LTR', 'name' => 'Liter', 'description' => 'Unit of volume'],
            ['code' => 'ML', 'name' => 'Milliliter', 'description' => 'Unit of volume (1/1000 liter)'],
            ['code' => 'GAL', 'name' => 'Gallon', 'description' => 'Unit of volume (imperial)'],

            // Length
            ['code' => 'MTR', 'name' => 'Meter', 'description' => 'Unit of length'],
            ['code' => 'CM', 'name' => 'Centimeter', 'description' => 'Unit of length (1/100 meter)'],
            ['code' => 'MM', 'name' => 'Millimeter', 'description' => 'Unit of length (1/1000 meter)'],
            ['code' => 'FT', 'name' => 'Feet', 'description' => 'Unit of length (imperial)'],
            ['code' => 'IN', 'name' => 'Inch', 'description' => 'Unit of length (imperial)'],

            // Packaging
            ['code' => 'BOX', 'name' => 'Box', 'description' => 'Boxed packaging'],
            ['code' => 'CTN', 'name' => 'Carton', 'description' => 'Carton packaging'],
            ['code' => 'PACK', 'name' => 'Pack', 'description' => 'Pack/package'],
            ['code' => 'BAG', 'name' => 'Bag', 'description' => 'Bag/sack packaging'],
            ['code' => 'ROLL', 'name' => 'Roll', 'description' => 'Roll packaging'],
            ['code' => 'PALLET', 'name' => 'Pallet', 'description' => 'Pallet load'],
        ];

        foreach ($uoms as $uom) {
            DB::table('mst_uom')->updateOrInsert(
                ['code' => $uom['code']],
                array_merge($uom, [
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command->info('Seeded ' . count($uoms) . ' units of measure.');
    }
}

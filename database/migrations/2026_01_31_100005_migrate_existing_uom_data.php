<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migrates existing UoM and Product data to the new multi-UoM structure.
     * This is a data migration that:
     * 1. Creates UoM categories
     * 2. Assigns categories to existing UoMs
     * 3. Sets standard conversion factors
     * 4. Creates product_uoms records from existing product.uom_id
     */
    public function up(): void
    {
        // Step 1: Insert UoM Categories
        $categories = [
            ['code' => 'WEIGHT', 'name' => 'Weight', 'description' => 'Units for measuring mass/weight (kg, g, ton, etc.)'],
            ['code' => 'VOLUME', 'name' => 'Volume', 'description' => 'Units for measuring volume/capacity (liter, ml, gallon, etc.)'],
            ['code' => 'LENGTH', 'name' => 'Length', 'description' => 'Units for measuring length/distance (meter, cm, feet, etc.)'],
            ['code' => 'COUNT', 'name' => 'Count', 'description' => 'Units for counting items (pieces, units, dozen, etc.)'],
            ['code' => 'PACKAGING', 'name' => 'Packaging', 'description' => 'Packaging units (box, carton, bag, pallet, etc.)'],
            ['code' => 'AREA', 'name' => 'Area', 'description' => 'Units for measuring area (m², ft², etc.)'],
            ['code' => 'TIME', 'name' => 'Time', 'description' => 'Time-based units (hour, day, etc.)'],
        ];

        foreach ($categories as $category) {
            DB::table('mst_uom_categories')->updateOrInsert(
                ['code' => $category['code']],
                array_merge($category, [
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // Get category IDs
        $catWeight = DB::table('mst_uom_categories')->where('code', 'WEIGHT')->value('id');
        $catVolume = DB::table('mst_uom_categories')->where('code', 'VOLUME')->value('id');
        $catLength = DB::table('mst_uom_categories')->where('code', 'LENGTH')->value('id');
        $catCount = DB::table('mst_uom_categories')->where('code', 'COUNT')->value('id');
        $catPackaging = DB::table('mst_uom_categories')->where('code', 'PACKAGING')->value('id');

        // Step 2: Get base UoM IDs (will be used for conversion references)
        $kgId = DB::table('mst_uom')->where('code', 'KG')->value('id');
        $ltrId = DB::table('mst_uom')->where('code', 'LTR')->value('id');
        $mtrId = DB::table('mst_uom')->where('code', 'MTR')->value('id');
        $pcsId = DB::table('mst_uom')->where('code', 'PCS')->value('id');

        // Step 3: Update existing UoMs with categories and conversions
        $uomUpdates = [
            // Weight (base: KG)
            ['code' => 'KG', 'category_id' => $catWeight, 'base_uom_id' => null, 'conversion_factor' => 1],
            ['code' => 'G', 'category_id' => $catWeight, 'base_uom_id' => $kgId, 'conversion_factor' => 0.001],
            ['code' => 'TON', 'category_id' => $catWeight, 'base_uom_id' => $kgId, 'conversion_factor' => 1000],
            ['code' => 'LB', 'category_id' => $catWeight, 'base_uom_id' => $kgId, 'conversion_factor' => 0.453592],

            // Volume (base: LTR)
            ['code' => 'LTR', 'category_id' => $catVolume, 'base_uom_id' => null, 'conversion_factor' => 1],
            ['code' => 'ML', 'category_id' => $catVolume, 'base_uom_id' => $ltrId, 'conversion_factor' => 0.001],
            ['code' => 'GAL', 'category_id' => $catVolume, 'base_uom_id' => $ltrId, 'conversion_factor' => 3.78541],

            // Length (base: MTR)
            ['code' => 'MTR', 'category_id' => $catLength, 'base_uom_id' => null, 'conversion_factor' => 1],
            ['code' => 'CM', 'category_id' => $catLength, 'base_uom_id' => $mtrId, 'conversion_factor' => 0.01],
            ['code' => 'MM', 'category_id' => $catLength, 'base_uom_id' => $mtrId, 'conversion_factor' => 0.001],
            ['code' => 'FT', 'category_id' => $catLength, 'base_uom_id' => $mtrId, 'conversion_factor' => 0.3048],
            ['code' => 'IN', 'category_id' => $catLength, 'base_uom_id' => $mtrId, 'conversion_factor' => 0.0254],

            // Count (base: PCS)
            ['code' => 'PCS', 'category_id' => $catCount, 'base_uom_id' => null, 'conversion_factor' => 1],
            ['code' => 'UNIT', 'category_id' => $catCount, 'base_uom_id' => $pcsId, 'conversion_factor' => 1],
            ['code' => 'SET', 'category_id' => $catCount, 'base_uom_id' => $pcsId, 'conversion_factor' => 1], // User should set per product
            ['code' => 'PAIR', 'category_id' => $catCount, 'base_uom_id' => $pcsId, 'conversion_factor' => 2],
            ['code' => 'DZN', 'category_id' => $catCount, 'base_uom_id' => $pcsId, 'conversion_factor' => 12],

            // Packaging (no standard base - product-specific)
            ['code' => 'BOX', 'category_id' => $catPackaging, 'base_uom_id' => null, 'conversion_factor' => null],
            ['code' => 'CTN', 'category_id' => $catPackaging, 'base_uom_id' => null, 'conversion_factor' => null],
            ['code' => 'PACK', 'category_id' => $catPackaging, 'base_uom_id' => null, 'conversion_factor' => null],
            ['code' => 'BAG', 'category_id' => $catPackaging, 'base_uom_id' => null, 'conversion_factor' => null],
            ['code' => 'ROLL', 'category_id' => $catPackaging, 'base_uom_id' => null, 'conversion_factor' => null],
            ['code' => 'PALLET', 'category_id' => $catPackaging, 'base_uom_id' => null, 'conversion_factor' => null],
        ];

        foreach ($uomUpdates as $update) {
            DB::table('mst_uom')
                ->where('code', $update['code'])
                ->update([
                    'category_id' => $update['category_id'],
                    'base_uom_id' => $update['base_uom_id'],
                    'conversion_factor' => $update['conversion_factor'],
                    'updated_at' => now(),
                ]);
        }

        // Step 4: Create product_uoms from existing products
        // Each product's current uom_id becomes its base UoM
        $products = DB::table('mst_products')
            ->whereNotNull('uom_id')
            ->select('id', 'uom_id')
            ->get();

        foreach ($products as $product) {
            DB::table('mst_product_uoms')->updateOrInsert(
                ['product_id' => $product->id, 'uom_id' => $product->uom_id],
                [
                    'is_base_uom' => true,
                    'is_purchase_uom' => true,
                    'is_sales_uom' => true,
                    'is_default_purchase' => true,
                    'is_default_sales' => true,
                    'conversion_factor' => 1,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Step 5: Set base_uom_id on existing order items
        // Join with product to get its base UoM
        DB::statement("
            UPDATE purchase_order_items poi
            JOIN mst_products p ON poi.product_id = p.id
            SET poi.base_uom_id = p.uom_id
            WHERE poi.base_uom_id IS NULL
        ");

        DB::statement("
            UPDATE sales_order_items soi
            JOIN mst_products p ON soi.product_id = p.id
            SET soi.base_uom_id = p.uom_id
            WHERE soi.base_uom_id IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear product_uoms (will be recreated on next migration up)
        DB::table('mst_product_uoms')->truncate();

        // Reset UoM category and conversion data
        DB::table('mst_uom')->update([
            'category_id' => null,
            'base_uom_id' => null,
            'conversion_factor' => null,
        ]);

        // Delete categories
        DB::table('mst_uom_categories')->delete();

        // Reset order items
        DB::table('purchase_order_items')->update(['base_uom_id' => null]);
        DB::table('sales_order_items')->update(['base_uom_id' => null]);
    }
};

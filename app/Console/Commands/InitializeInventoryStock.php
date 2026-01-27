<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\InventoryStock;
use App\Models\InventoryMovement;

class InitializeInventoryStock extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'inventory:initialize
                            {--dry-run : Show what would be initialized without making changes}
                            {--with-opening-movements : Create opening stock movements for each product}';

    /**
     * The console command description.
     */
    protected $description = 'Initialize inventory_stock table from current mst_products.stock_quantity values';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $withMovements = $this->option('with-opening-movements');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        $products = Product::all();

        if ($products->isEmpty()) {
            $this->info('No products found to initialize.');
            return Command::SUCCESS;
        }

        $this->info("Found {$products->count()} products to initialize inventory for.");

        $initializedCount = 0;
        $skippedCount = 0;

        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        foreach ($products as $product) {
            // Check if inventory stock already exists
            $existingStock = InventoryStock::where('product_id', $product->id)->first();

            if ($existingStock) {
                $skippedCount++;
                $bar->advance();
                continue;
            }

            if (!$dryRun) {
                // Create inventory stock record
                $stock = InventoryStock::create([
                    'product_id' => $product->id,
                    'quantity_on_hand' => $product->stock_quantity ?? 0,
                    'quantity_reserved' => 0,
                    'quantity_available' => $product->stock_quantity ?? 0,
                    'last_cost' => $product->cost_price,
                    'average_cost' => $product->cost_price,
                    'last_movement_at' => now(),
                ]);

                // Create opening stock movement if requested and quantity > 0
                if ($withMovements && ($product->stock_quantity ?? 0) > 0) {
                    InventoryMovement::create([
                        'movement_date' => now(),
                        'product_id' => $product->id,
                        'movement_type' => InventoryMovement::TYPE_OPENING_STOCK,
                        'reference_type' => 'initialization',
                        'reference_id' => null,
                        'quantity' => $product->stock_quantity,
                        'unit_cost' => $product->cost_price,
                        'quantity_before' => 0,
                        'quantity_after' => $product->stock_quantity,
                        'notes' => 'Opening stock from legacy mst_products.stock_quantity',
                    ]);
                }
            }

            $initializedCount++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Initialization Summary:");
        $this->table(
            ['Action', 'Count'],
            [
                ['Inventory Records Created', $initializedCount],
                ['Skipped (already exists)', $skippedCount],
            ]
        );

        if (!$dryRun) {
            $this->info('Inventory stock has been initialized successfully.');
        }

        return Command::SUCCESS;
    }
}

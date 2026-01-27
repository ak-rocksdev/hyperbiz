<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\InventoryStock;
use App\Models\InventoryMovement;

class MigrateLegacyTransactions extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'transactions:migrate-legacy
                            {--dry-run : Show what would be migrated without making changes}
                            {--with-inventory : Also create inventory movements for the migrated transactions}';

    /**
     * The console command description.
     */
    protected $description = 'Migrate legacy transactions table to new purchase_orders and sales_orders structure';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $withInventory = $this->option('with-inventory');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        $transactions = Transaction::with('details.product', 'customer')->get();

        if ($transactions->isEmpty()) {
            $this->info('No legacy transactions found to migrate.');
            return Command::SUCCESS;
        }

        $this->info("Found {$transactions->count()} transactions to migrate.");

        $purchaseCount = 0;
        $salesCount = 0;
        $skippedCount = 0;

        $bar = $this->output->createProgressBar($transactions->count());
        $bar->start();

        foreach ($transactions as $transaction) {
            $transactionType = $transaction->transaction_type ?? 'sell';

            if ($transactionType === 'purchase') {
                if (!$dryRun) {
                    $this->migrateToPurchaseOrder($transaction, $withInventory);
                }
                $purchaseCount++;
            } elseif ($transactionType === 'sell') {
                if (!$dryRun) {
                    $this->migrateToSalesOrder($transaction, $withInventory);
                }
                $salesCount++;
            } else {
                $skippedCount++;
                $this->warn("Skipped transaction {$transaction->transaction_code} - unknown type: {$transactionType}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Migration Summary:");
        $this->table(
            ['Type', 'Count'],
            [
                ['Purchase Orders Created', $purchaseCount],
                ['Sales Orders Created', $salesCount],
                ['Skipped', $skippedCount],
            ]
        );

        if (!$dryRun) {
            $this->info('Legacy transactions have been migrated successfully.');
            $this->warn('Note: The original transactions table has NOT been deleted. You can review and delete it manually when ready.');
        }

        return Command::SUCCESS;
    }

    /**
     * Migrate a transaction to a Purchase Order.
     */
    protected function migrateToPurchaseOrder(Transaction $transaction, bool $withInventory): void
    {
        DB::transaction(function () use ($transaction, $withInventory) {
            // Create Purchase Order
            $po = PurchaseOrder::create([
                'po_number' => 'PO-LEGACY-' . $transaction->transaction_code,
                'supplier_id' => $transaction->mst_client_id,
                'order_date' => $transaction->transaction_date ?? $transaction->created_at,
                'status' => $this->mapStatus($transaction->status, 'purchase'),
                'currency_code' => 'IDR',
                'exchange_rate' => 1,
                'subtotal' => $transaction->grand_total - ($transaction->expedition_fee ?? 0),
                'grand_total' => $transaction->grand_total,
                'payment_status' => 'paid', // Assume legacy transactions are paid
                'amount_paid' => $transaction->grand_total,
                'notes' => "Migrated from legacy transaction: {$transaction->transaction_code}",
                'created_by' => $transaction->created_by,
                'updated_by' => $transaction->updated_by,
                'created_at' => $transaction->created_at,
                'updated_at' => $transaction->updated_at,
            ]);

            // Create Purchase Order Items
            foreach ($transaction->details as $detail) {
                $item = PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $detail->mst_product_id,
                    'quantity' => $detail->quantity,
                    'quantity_received' => $detail->quantity, // Assume fully received
                    'unit_cost' => $detail->price,
                    'subtotal' => $detail->quantity * $detail->price,
                    'created_at' => $detail->created_at,
                    'updated_at' => $detail->updated_at,
                ]);

                // Create inventory movement if requested
                if ($withInventory && $detail->mst_product_id) {
                    $this->createInventoryMovement(
                        $detail->mst_product_id,
                        InventoryMovement::TYPE_PURCHASE_IN,
                        $detail->quantity,
                        'purchase_order',
                        $po->id,
                        $detail->price,
                        "Migrated from legacy transaction: {$transaction->transaction_code}"
                    );
                }
            }
        });
    }

    /**
     * Migrate a transaction to a Sales Order.
     */
    protected function migrateToSalesOrder(Transaction $transaction, bool $withInventory): void
    {
        DB::transaction(function () use ($transaction, $withInventory) {
            // Create Sales Order
            $so = SalesOrder::create([
                'so_number' => 'SO-LEGACY-' . $transaction->transaction_code,
                'customer_id' => $transaction->mst_client_id,
                'order_date' => $transaction->transaction_date ?? $transaction->created_at,
                'status' => $this->mapStatus($transaction->status, 'sales'),
                'currency_code' => 'IDR',
                'exchange_rate' => 1,
                'subtotal' => $transaction->grand_total - ($transaction->expedition_fee ?? 0),
                'shipping_fee' => $transaction->expedition_fee ?? 0,
                'grand_total' => $transaction->grand_total,
                'payment_status' => 'paid', // Assume legacy transactions are paid
                'amount_paid' => $transaction->grand_total,
                'notes' => "Migrated from legacy transaction: {$transaction->transaction_code}",
                'created_by' => $transaction->created_by,
                'updated_by' => $transaction->updated_by,
                'created_at' => $transaction->created_at,
                'updated_at' => $transaction->updated_at,
            ]);

            // Create Sales Order Items
            foreach ($transaction->details as $detail) {
                $item = SalesOrderItem::create([
                    'sales_order_id' => $so->id,
                    'product_id' => $detail->mst_product_id,
                    'quantity' => $detail->quantity,
                    'quantity_shipped' => $detail->quantity, // Assume fully shipped
                    'unit_price' => $detail->price,
                    'subtotal' => $detail->quantity * $detail->price,
                    'created_at' => $detail->created_at,
                    'updated_at' => $detail->updated_at,
                ]);

                // Create inventory movement if requested
                if ($withInventory && $detail->mst_product_id) {
                    $this->createInventoryMovement(
                        $detail->mst_product_id,
                        InventoryMovement::TYPE_SALES_OUT,
                        $detail->quantity,
                        'sales_order',
                        $so->id,
                        null, // No cost tracking for legacy
                        "Migrated from legacy transaction: {$transaction->transaction_code}"
                    );
                }
            }
        });
    }

    /**
     * Map legacy status to new status.
     */
    protected function mapStatus(?string $status, string $type): string
    {
        $status = strtolower($status ?? 'completed');

        if ($type === 'purchase') {
            return match($status) {
                'pending' => PurchaseOrder::STATUS_DRAFT,
                'completed' => PurchaseOrder::STATUS_RECEIVED,
                'canceled', 'cancelled' => PurchaseOrder::STATUS_CANCELLED,
                default => PurchaseOrder::STATUS_RECEIVED,
            };
        } else {
            return match($status) {
                'pending' => SalesOrder::STATUS_DRAFT,
                'completed' => SalesOrder::STATUS_DELIVERED,
                'canceled', 'cancelled' => SalesOrder::STATUS_CANCELLED,
                default => SalesOrder::STATUS_DELIVERED,
            };
        }
    }

    /**
     * Create inventory movement and update stock.
     */
    protected function createInventoryMovement(
        int $productId,
        string $movementType,
        float $quantity,
        string $referenceType,
        int $referenceId,
        ?float $unitCost,
        string $notes
    ): void {
        $stock = InventoryStock::getOrCreate($productId);
        $quantityBefore = $stock->quantity_on_hand;

        // Adjust stock
        $isIn = in_array($movementType, [
            InventoryMovement::TYPE_PURCHASE_IN,
            InventoryMovement::TYPE_SALES_RETURN,
            InventoryMovement::TYPE_ADJUSTMENT_IN,
            InventoryMovement::TYPE_OPENING_STOCK,
        ]);

        if ($isIn) {
            $stock->addStock($quantity, $unitCost);
            $movementQty = $quantity;
        } else {
            $stock->deductStock($quantity);
            $movementQty = -$quantity;
        }

        // Create movement record
        InventoryMovement::create([
            'movement_date' => now(),
            'product_id' => $productId,
            'movement_type' => $movementType,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'quantity' => $movementQty,
            'unit_cost' => $unitCost,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $stock->quantity_on_hand,
            'notes' => $notes,
        ]);
    }
}

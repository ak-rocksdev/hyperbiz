<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryStock extends Model
{
    protected $table = 'inventory_stock';

    protected $fillable = [
        'product_id',
        'quantity_on_hand',
        'quantity_reserved',
        'quantity_available',
        'reorder_level',
        'last_cost',
        'average_cost',
        'last_movement_at',
    ];

    protected $casts = [
        'quantity_on_hand' => 'decimal:3',
        'quantity_reserved' => 'decimal:3',
        'quantity_available' => 'decimal:3',
        'reorder_level' => 'decimal:3',
        'last_cost' => 'decimal:2',
        'average_cost' => 'decimal:2',
        'last_movement_at' => 'datetime',
    ];

    /**
     * Product this stock record belongs to.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get or create stock record for a product.
     */
    public static function getOrCreate(int $productId): self
    {
        return static::firstOrCreate(
            ['product_id' => $productId],
            [
                'quantity_on_hand' => 0,
                'quantity_reserved' => 0,
                'quantity_available' => 0,
            ]
        );
    }

    /**
     * Update available quantity (on_hand - reserved).
     */
    public function updateAvailable(): self
    {
        $this->quantity_available = $this->quantity_on_hand - $this->quantity_reserved;
        $this->save();
        return $this;
    }

    /**
     * Add stock (from purchase receiving).
     */
    public function addStock(float $quantity, float $unitCost = null): self
    {
        $this->quantity_on_hand += $quantity;
        $this->quantity_available = $this->quantity_on_hand - $this->quantity_reserved;
        $this->last_movement_at = now();

        if ($unitCost !== null) {
            $this->last_cost = $unitCost;
            // Update average cost
            // Formula: ((old_qty * old_avg) + (new_qty * new_cost)) / total_qty
            $oldQty = $this->quantity_on_hand - $quantity;
            $oldAvg = $this->average_cost ?? $unitCost;
            if ($this->quantity_on_hand > 0) {
                $this->average_cost = (($oldQty * $oldAvg) + ($quantity * $unitCost)) / $this->quantity_on_hand;
            }
        }

        $this->save();
        return $this;
    }

    /**
     * Deduct stock (from sales shipment).
     */
    public function deductStock(float $quantity): self
    {
        $this->quantity_on_hand -= $quantity;
        $this->quantity_available = $this->quantity_on_hand - $this->quantity_reserved;
        $this->last_movement_at = now();
        $this->save();
        return $this;
    }

    /**
     * Reserve stock (when SO is confirmed).
     */
    public function reserveStock(float $quantity): self
    {
        $this->quantity_reserved += $quantity;
        $this->quantity_available = $this->quantity_on_hand - $this->quantity_reserved;
        $this->save();
        return $this;
    }

    /**
     * Release reserved stock (when SO is cancelled or shipped).
     */
    public function releaseReserved(float $quantity): self
    {
        $this->quantity_reserved -= $quantity;
        $this->quantity_available = $this->quantity_on_hand - $this->quantity_reserved;
        $this->save();
        return $this;
    }

    /**
     * Check if quantity is available.
     */
    public function hasAvailable(float $quantity): bool
    {
        return $this->quantity_available >= $quantity;
    }
}

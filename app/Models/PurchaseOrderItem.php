<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $table = 'purchase_order_items';

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'uom_id',
        'conversion_factor',
        'uom_conversion_factor',
        'base_uom_id',
        'quantity',
        'base_quantity',
        'quantity_received',
        'unit_cost',
        'discount_percentage',
        'subtotal',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'base_quantity' => 'decimal:3',
        'quantity_received' => 'decimal:3',
        'conversion_factor' => 'decimal:6',
        'uom_conversion_factor' => 'decimal:6',
        'unit_cost' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Purchase order this item belongs to.
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Product for this line item.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Unit of measure.
     */
    public function uom()
    {
        return $this->belongsTo(Uom::class);
    }

    /**
     * Base unit of measure (for conversion reference).
     */
    public function baseUom()
    {
        return $this->belongsTo(Uom::class, 'base_uom_id');
    }

    /**
     * Calculate subtotal before saving.
     */
    public function calculateSubtotal(): self
    {
        $gross = $this->quantity * $this->unit_cost;
        $discount = $gross * ($this->discount_percentage / 100);
        $this->subtotal = $gross - $discount;
        return $this;
    }

    /**
     * Calculate and set base quantity from quantity and conversion factor.
     */
    public function calculateBaseQuantity(): self
    {
        $factor = $this->conversion_factor ?? $this->uom_conversion_factor ?? 1;
        $this->base_quantity = $this->quantity * $factor;
        return $this;
    }

    /**
     * Get remaining quantity to receive (in order UoM).
     */
    public function getRemainingQuantityAttribute(): float
    {
        return $this->quantity - $this->quantity_received;
    }

    /**
     * Get remaining base quantity to receive.
     */
    public function getRemainingBaseQuantityAttribute(): float
    {
        $factor = $this->conversion_factor ?? $this->uom_conversion_factor ?? 1;
        return ($this->quantity - $this->quantity_received) * $factor;
    }

    /**
     * Check if fully received.
     */
    public function isFullyReceived(): bool
    {
        return $this->quantity_received >= $this->quantity;
    }

    /**
     * Get the effective conversion factor.
     */
    public function getEffectiveConversionFactor(): float
    {
        return $this->conversion_factor ?? $this->uom_conversion_factor ?? 1;
    }
}

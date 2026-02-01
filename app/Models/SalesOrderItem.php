<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToCompany;

class SalesOrderItem extends Model
{
    use BelongsToCompany;
    protected $table = 'sales_order_items';

    protected $fillable = [
        'sales_order_id',
        'product_id',
        'uom_id',
        'uom_conversion_factor',
        'base_uom_id',
        'quantity',
        'base_quantity',
        'quantity_shipped',
        'unit_price',
        'unit_cost',
        'discount_percentage',
        'subtotal',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'base_quantity' => 'decimal:3',
        'quantity_shipped' => 'decimal:3',
        'uom_conversion_factor' => 'decimal:6',
        'unit_price' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Sales order this item belongs to.
     */
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
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
     * Calculate subtotal.
     */
    public function calculateSubtotal(): self
    {
        $gross = $this->quantity * $this->unit_price;
        $discount = $gross * ($this->discount_percentage / 100);
        $this->subtotal = $gross - $discount;
        return $this;
    }

    /**
     * Calculate and set base quantity from quantity and conversion factor.
     */
    public function calculateBaseQuantity(): self
    {
        $factor = $this->uom_conversion_factor ?? 1;
        $this->base_quantity = $this->quantity * $factor;
        return $this;
    }

    /**
     * Get remaining quantity to ship (in order UoM).
     */
    public function getRemainingQuantityAttribute(): float
    {
        return $this->quantity - $this->quantity_shipped;
    }

    /**
     * Get remaining base quantity to ship.
     */
    public function getRemainingBaseQuantityAttribute(): float
    {
        $factor = $this->uom_conversion_factor ?? 1;
        return ($this->quantity - $this->quantity_shipped) * $factor;
    }

    /**
     * Get line item profit.
     */
    public function getLineProfitAttribute(): float
    {
        $revenue = $this->subtotal;
        $cost = $this->quantity * ($this->unit_cost ?? 0);
        return $revenue - $cost;
    }

    /**
     * Get the effective conversion factor.
     */
    public function getEffectiveConversionFactor(): float
    {
        return $this->uom_conversion_factor ?? 1;
    }
}

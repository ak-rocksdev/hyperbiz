<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrderItem extends Model
{
    protected $table = 'sales_order_items';

    protected $fillable = [
        'sales_order_id',
        'product_id',
        'uom_id',
        'quantity',
        'quantity_shipped',
        'unit_price',
        'unit_cost',
        'discount_percentage',
        'subtotal',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'quantity_shipped' => 'decimal:3',
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
     * Get remaining quantity to ship.
     */
    public function getRemainingQuantityAttribute(): float
    {
        return $this->quantity - $this->quantity_shipped;
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
}

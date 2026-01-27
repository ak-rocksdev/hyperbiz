<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesReturnItem extends Model
{
    protected $table = 'sales_return_items';

    protected $fillable = [
        'sales_return_id',
        'product_id',
        'uom_id',
        'quantity',
        'unit_price',
        'unit_cost',
        'subtotal',
        'reason',
        'condition',
        'restock',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'restock' => 'boolean',
    ];

    const CONDITION_GOOD = 'good';
    const CONDITION_DAMAGED = 'damaged';
    const CONDITION_EXPIRED = 'expired';

    /**
     * Return this item belongs to.
     */
    public function salesReturn()
    {
        return $this->belongsTo(SalesReturn::class);
    }

    /**
     * Product for this item.
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
        $this->subtotal = $this->quantity * $this->unit_price;
        return $this;
    }

    /**
     * Get condition label.
     */
    public function getConditionLabelAttribute(): string
    {
        return match($this->condition) {
            self::CONDITION_GOOD => 'Good',
            self::CONDITION_DAMAGED => 'Damaged',
            self::CONDITION_EXPIRED => 'Expired',
            default => $this->condition,
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToCompany;

class PurchaseReturnItem extends Model
{
    use BelongsToCompany;
    protected $table = 'purchase_return_items';

    protected $fillable = [
        'purchase_return_id',
        'product_id',
        'uom_id',
        'quantity',
        'unit_cost',
        'subtotal',
        'reason',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Return this item belongs to.
     */
    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class);
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
        $this->subtotal = $this->quantity * $this->unit_cost;
        return $this;
    }
}

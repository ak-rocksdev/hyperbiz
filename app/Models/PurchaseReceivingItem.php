<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReceivingItem extends Model
{
    protected $table = 'purchase_receiving_items';

    protected $fillable = [
        'purchase_receiving_id',
        'purchase_order_item_id',
        'product_id',
        'quantity_received',
        'unit_cost',
        'notes',
    ];

    protected $casts = [
        'quantity_received' => 'decimal:3',
        'unit_cost' => 'decimal:2',
    ];

    /**
     * Receiving record this item belongs to.
     */
    public function purchaseReceiving()
    {
        return $this->belongsTo(PurchaseReceiving::class);
    }

    /**
     * PO item this receiving is for.
     */
    public function purchaseOrderItem()
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    /**
     * Product for this item.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

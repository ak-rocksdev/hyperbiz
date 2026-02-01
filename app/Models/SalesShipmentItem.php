<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToCompany;

class SalesShipmentItem extends Model
{
    use BelongsToCompany;
    protected $table = 'sales_shipment_items';

    protected $fillable = [
        'sales_shipment_id',
        'sales_order_item_id',
        'product_id',
        'quantity_shipped',
        'notes',
    ];

    protected $casts = [
        'quantity_shipped' => 'decimal:3',
    ];

    /**
     * Shipment this item belongs to.
     */
    public function salesShipment()
    {
        return $this->belongsTo(SalesShipment::class);
    }

    /**
     * SO item this shipment is for.
     */
    public function salesOrderItem()
    {
        return $this->belongsTo(SalesOrderItem::class);
    }

    /**
     * Product for this item.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Traits\LogsSystemChanges;

class Product extends Model
{
    use LogsSystemChanges;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_by = Auth::id();
        });

        static::updating(function ($model) {
            $model->updated_by = Auth::id();
        });
    }

    protected $table = 'mst_products';
    
    protected $fillable = [
        'name',
        'description',
        'sku',
        'barcode',
        'price',
        'cost_price',
        'currency',
        'stock_quantity',
        'min_stock_level',
        'uom_id',
        'mst_product_category_id',
        'mst_brand_id',
        'mst_client_id',
        'weight',
        'dimensions',
        'image_url',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $attributes = [
        'currency' => 'IDR', // Set your default currency here
    ];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'mst_product_category_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'mst_brand_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'mst_client_id');
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class);
    }

    public function inventoryStock()
    {
        return $this->hasOne(InventoryStock::class);
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    // Order Item Relationships
    public function salesOrderItems()
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function salesShipmentItems()
    {
        return $this->hasMany(SalesShipmentItem::class);
    }

    public function purchaseReceivingItems()
    {
        return $this->hasMany(PurchaseReceivingItem::class);
    }

    // Stock Accessors (use InventoryStock as single source of truth)
    public function getQuantityOnHandAttribute()
    {
        return $this->inventoryStock?->quantity_on_hand ?? 0;
    }

    public function getQuantityAvailableAttribute()
    {
        return $this->inventoryStock?->quantity_available ?? 0;
    }

    public function getQuantityReservedAttribute()
    {
        return $this->inventoryStock?->quantity_reserved ?? 0;
    }

    public function getAverageCostAttribute()
    {
        return $this->inventoryStock?->average_cost ?? $this->cost_price ?? 0;
    }

    public function getReorderLevelAttribute()
    {
        return $this->inventoryStock?->reorder_level ?? $this->min_stock_level ?? 0;
    }

    public function getIsLowStockAttribute()
    {
        $stock = $this->inventoryStock;
        if (!$stock) return false;

        $onHand = $stock->quantity_on_hand;
        $reorder = $stock->reorder_level ?? 0;

        // Low stock: reorder_level > 0 AND quantity_on_hand <= reorder_level AND quantity_on_hand > 0
        // (not out_of_stock, which is quantity_on_hand <= 0)
        return $reorder > 0 && $onHand > 0 && $onHand <= $reorder;
    }

    public function getStockStatusAttribute()
    {
        $stock = $this->inventoryStock;
        if (!$stock) return 'no_stock';

        $onHand = $stock->quantity_on_hand;
        $reorder = $stock->reorder_level ?? 0;

        if ($onHand <= 0) return 'out_of_stock';
        if ($reorder > 0 && $onHand <= $reorder) return 'low_stock';
        return 'in_stock';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Traits\LogsSystemChanges;
use App\Traits\BelongsToCompany;

class Product extends Model
{
    use LogsSystemChanges, BelongsToCompany;

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

    /**
     * Base/default UoM for this product (legacy single-UoM field).
     */
    public function uom()
    {
        return $this->belongsTo(Uom::class);
    }

    /**
     * All UoM configurations for this product (multi-UoM support).
     */
    public function productUoms()
    {
        return $this->hasMany(ProductUom::class);
    }

    /**
     * Active UoM configurations.
     */
    public function activeProductUoms()
    {
        return $this->productUoms()->where('is_active', true);
    }

    /**
     * Get the base UoM configuration for this product.
     */
    public function getBaseProductUom(): ?ProductUom
    {
        return $this->productUoms()->where('is_base_uom', true)->first();
    }

    /**
     * Get UoMs available for purchase.
     */
    public function getPurchaseUoms()
    {
        return $this->activeProductUoms()->where('is_purchase_uom', true)->with('uom')->get();
    }

    /**
     * Get UoMs available for sales.
     */
    public function getSalesUoms()
    {
        return $this->activeProductUoms()->where('is_sales_uom', true)->with('uom')->get();
    }

    /**
     * Get default purchase UoM configuration.
     */
    public function getDefaultPurchaseUom(): ?ProductUom
    {
        return $this->activeProductUoms()
            ->where('is_purchase_uom', true)
            ->orderByDesc('is_default_purchase')
            ->orderByDesc('is_base_uom')
            ->first();
    }

    /**
     * Get default sales UoM configuration.
     */
    public function getDefaultSalesUom(): ?ProductUom
    {
        return $this->activeProductUoms()
            ->where('is_sales_uom', true)
            ->orderByDesc('is_default_sales')
            ->orderByDesc('is_base_uom')
            ->first();
    }

    /**
     * Check if product has multi-UoM configured.
     */
    public function hasMultipleUoms(): bool
    {
        return $this->activeProductUoms()->count() > 1;
    }

    /**
     * Convert quantity from a given UoM to base UoM.
     *
     * @param float $quantity
     * @param int $uomId
     * @return float
     */
    public function convertToBaseUom(float $quantity, int $uomId): float
    {
        $productUom = $this->productUoms()->where('uom_id', $uomId)->first();
        if (!$productUom) {
            return $quantity; // Assume 1:1 if not configured
        }
        return $productUom->convertToBase($quantity);
    }

    /**
     * Convert quantity from base UoM to a given UoM.
     *
     * @param float $baseQuantity
     * @param int $uomId
     * @return float
     */
    public function convertFromBaseUom(float $baseQuantity, int $uomId): float
    {
        $productUom = $this->productUoms()->where('uom_id', $uomId)->first();
        if (!$productUom) {
            return $baseQuantity; // Assume 1:1 if not configured
        }
        return $productUom->convertFromBase($baseQuantity);
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

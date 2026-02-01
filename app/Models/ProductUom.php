<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use App\Traits\BelongsToCompany;

class ProductUom extends Model
{
    use BelongsToCompany;
    protected $table = 'mst_product_uoms';

    protected $fillable = [
        'product_id',
        'uom_id',
        'is_base_uom',
        'is_purchase_uom',
        'is_sales_uom',
        'is_default_purchase',
        'is_default_sales',
        'conversion_factor',
        'default_purchase_price',
        'default_sales_price',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_base_uom' => 'boolean',
        'is_purchase_uom' => 'boolean',
        'is_sales_uom' => 'boolean',
        'is_default_purchase' => 'boolean',
        'is_default_sales' => 'boolean',
        'conversion_factor' => 'decimal:6',
        'default_purchase_price' => 'decimal:2',
        'default_sales_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_by = Auth::id();
        });

        static::updating(function ($model) {
            $model->updated_by = Auth::id();
        });

        // Ensure only one base UoM per product
        static::saving(function ($model) {
            if ($model->is_base_uom) {
                self::where('product_id', $model->product_id)
                    ->where('id', '!=', $model->id ?? 0)
                    ->update(['is_base_uom' => false]);
            }

            // Ensure only one default purchase UoM per product
            if ($model->is_default_purchase) {
                self::where('product_id', $model->product_id)
                    ->where('id', '!=', $model->id ?? 0)
                    ->update(['is_default_purchase' => false]);
            }

            // Ensure only one default sales UoM per product
            if ($model->is_default_sales) {
                self::where('product_id', $model->product_id)
                    ->where('id', '!=', $model->id ?? 0)
                    ->update(['is_default_sales' => false]);
            }
        });
    }

    /**
     * Product this configuration belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * UoM referenced by this configuration.
     */
    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class);
    }

    /**
     * Convert quantity from this UoM to the product's base UoM.
     *
     * @param float $quantity
     * @return float
     */
    public function convertToBase(float $quantity): float
    {
        return $quantity * ($this->conversion_factor ?? 1);
    }

    /**
     * Convert quantity from the product's base UoM to this UoM.
     *
     * @param float $baseQuantity
     * @return float
     */
    public function convertFromBase(float $baseQuantity): float
    {
        $factor = $this->conversion_factor ?? 1;
        return $factor > 0 ? $baseQuantity / $factor : $baseQuantity;
    }

    /**
     * Scope for active configurations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for base UoM only.
     */
    public function scopeBaseOnly($query)
    {
        return $query->where('is_base_uom', true);
    }

    /**
     * Scope for purchase-enabled UoMs.
     */
    public function scopeForPurchase($query)
    {
        return $query->where('is_purchase_uom', true);
    }

    /**
     * Scope for sales-enabled UoMs.
     */
    public function scopeForSales($query)
    {
        return $query->where('is_sales_uom', true);
    }
}

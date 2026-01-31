<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Uom extends Model
{
    protected $table = 'mst_uom';

    protected $fillable = [
        'code',
        'name',
        'description',
        'category_id',
        'base_uom_id',
        'conversion_factor',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'conversion_factor' => 'decimal:6',
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
    }

    /**
     * Category this UoM belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(UomCategory::class, 'category_id');
    }

    /**
     * Base UoM for conversion (e.g., Gram -> KG).
     * NULL means this IS the base UoM for its category.
     */
    public function baseUom(): BelongsTo
    {
        return $this->belongsTo(Uom::class, 'base_uom_id');
    }

    /**
     * Derived UoMs that use this as their base.
     */
    public function derivedUoms(): HasMany
    {
        return $this->hasMany(Uom::class, 'base_uom_id');
    }

    /**
     * Products using this as their base UoM.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'uom_id');
    }

    /**
     * Product UoM configurations using this UoM.
     */
    public function productUoms(): HasMany
    {
        return $this->hasMany(ProductUom::class, 'uom_id');
    }

    /**
     * Check if this is a base UoM (no parent).
     */
    public function isBaseUom(): bool
    {
        return $this->base_uom_id === null;
    }

    /**
     * Get the actual base UoM for this unit.
     * Returns self if this is already the base.
     */
    public function getBaseUom(): self
    {
        return $this->isBaseUom() ? $this : $this->baseUom;
    }

    /**
     * Convert a quantity from this UoM to the base UoM.
     *
     * @param float $quantity
     * @return float
     */
    public function convertToBase(float $quantity): float
    {
        if ($this->isBaseUom() || !$this->conversion_factor) {
            return $quantity;
        }

        return $quantity * $this->conversion_factor;
    }

    /**
     * Convert a quantity from the base UoM to this UoM.
     *
     * @param float $baseQuantity
     * @return float
     */
    public function convertFromBase(float $baseQuantity): float
    {
        if ($this->isBaseUom() || !$this->conversion_factor) {
            return $baseQuantity;
        }

        return $baseQuantity / $this->conversion_factor;
    }

    /**
     * Scope for active UoMs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for base UoMs only (no parent).
     */
    public function scopeBaseOnly($query)
    {
        return $query->whereNull('base_uom_id');
    }

    /**
     * Scope by category.
     */
    public function scopeInCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }
}

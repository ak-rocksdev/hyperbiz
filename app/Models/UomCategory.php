<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class UomCategory extends Model
{
    protected $table = 'mst_uom_categories';

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
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
    }

    /**
     * UoMs belonging to this category.
     */
    public function uoms(): HasMany
    {
        return $this->hasMany(Uom::class, 'category_id');
    }

    /**
     * Get the base UoM for this category (the one without base_uom_id).
     */
    public function baseUom()
    {
        return $this->uoms()->whereNull('base_uom_id')->first();
    }

    /**
     * Scope for active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

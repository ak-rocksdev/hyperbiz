<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Auth;

class Uom extends Model
{
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

    protected $table = 'mst_uom';

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

    /**
     * Products using this UOM.
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'uom_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsSystemChanges;
use App\Traits\BelongsToCompany;

class ProductCategory extends Model
{
    use LogsSystemChanges, BelongsToCompany;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_by = auth()->id();
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });
    }

    protected $table = 'mst_product_categories';

    protected $fillable = [
        'name',
        'parent_id',
        'created_by',
        'updated_by'
    ];

    public function parent()
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'mst_product_category_id');
    }
}

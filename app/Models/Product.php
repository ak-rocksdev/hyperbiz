<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Auth;

class Product extends Model
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

    public function client()
    {
        return $this->belongsTo(Client::class, 'mst_client_id');
    }
}

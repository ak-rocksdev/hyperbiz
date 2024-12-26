<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Auth;

class Transaction extends Model
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
    
    protected $table = 'transactions';
    
    protected $fillable = [
        'mst_client_id',
        'mst_product_id',
        'quantity',
        'total_price',
        'transaction_date',
        'status',
        'created_by',
        'updated_by'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'mst_product_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'mst_client_id');
    }
}

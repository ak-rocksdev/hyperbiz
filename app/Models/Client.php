<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\ClientFactory::new();
    }

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

    protected $table = 'mst_client';

    protected $fillable = [
        'client_name',
        'mst_address_id',
        'client_phone_number',
        'email',
        'contact_person',
        'contact_person_phone_number',
        'mst_client_type_id',
        'is_customer',
        'created_by',
        'updated_by',
    ];

    /**
     * Relationship with Address
     */
    public function address()
    {
        return $this->belongsTo(Address::class, 'mst_address_id');
    }

    /**
     * Relationship with ClientType
     */
    public function clientType()
    {
        return $this->belongsTo(ClientType::class, 'mst_client_type_id');
    }

    /**
     * Relationship with Product
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'mst_client_id');
    }

    /**
     * Get transaction total value where 'transaction_type' is purchase or sell
     */
    public function getTotalSell()
    {
        return $this->hasMany(Transaction::class, 'mst_client_id')
            ->where('transaction_type', 'sell');
    }

    public function getTotalPurchase()
    {
        return $this->hasMany(Transaction::class, 'mst_client_id')
            ->where('transaction_type', 'purchase');
    }

    /**
     * Relationship with Transaction
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'mst_client_id');
    }
}


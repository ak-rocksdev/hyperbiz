<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsSystemChanges;

class Customer extends Model
{
    use HasFactory, LogsSystemChanges;

    protected static function newFactory()
    {
        return \Database\Factories\CustomerFactory::new();
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
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_customer' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Relationship with Address
     */
    public function address()
    {
        return $this->belongsTo(Address::class, 'mst_address_id');
    }

    /**
     * Relationship with CustomerType
     */
    public function customerType()
    {
        return $this->belongsTo(CustomerType::class, 'mst_client_type_id');
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

    /**
     * Accessor for customer name (maps to client_name column)
     */
    public function getNameAttribute()
    {
        return $this->client_name;
    }

    /**
     * Accessor for phone number (maps to client_phone_number column)
     */
    public function getPhoneNumberAttribute()
    {
        return $this->client_phone_number;
    }

    /**
     * Scope to filter only active customers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter only inactive customers
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Relationship with Sales Orders
     */
    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class, 'customer_id');
    }

    /**
     * Relationship with Sales Returns
     */
    public function salesReturns()
    {
        return $this->hasMany(SalesReturn::class, 'customer_id');
    }
}

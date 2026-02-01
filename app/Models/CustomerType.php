<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsSystemChanges;
use App\Traits\BelongsToCompany;

class CustomerType extends Model
{
    use HasFactory, LogsSystemChanges, BelongsToCompany;

    protected static function newFactory()
    {
        return \Database\Factories\CustomerTypeFactory::new();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_by = auth()->id() ?? "Seeder";
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });
    }

    protected $table = 'mst_client_type';

    protected $fillable = [
        'client_type',
        'can_purchase',
        'can_sell',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'can_purchase' => 'boolean',
        'can_sell' => 'boolean',
    ];

    /**
     * Relationship with Customers
     */
    public function customers()
    {
        return $this->hasMany(Customer::class, 'mst_client_type_id');
    }

    /**
     * Accessor for type name (maps to client_type column)
     */
    public function getNameAttribute()
    {
        return $this->client_type;
    }

    /**
     * Scope for types that can be used for purchasing.
     */
    public function scopeCanPurchase($query)
    {
        return $query->where('can_purchase', true);
    }

    /**
     * Scope for types that can be used for selling.
     */
    public function scopeCanSell($query)
    {
        return $query->where('can_sell', true);
    }
}

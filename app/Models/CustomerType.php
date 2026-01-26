<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsSystemChanges;

class CustomerType extends Model
{
    use HasFactory, LogsSystemChanges;

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
        'created_by',
        'updated_by',
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
}

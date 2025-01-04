<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsSystemChanges;

class ClientType extends Model
{
    use HasFactory, LogsSystemChanges;

    protected static function newFactory()
    {
        return \Database\Factories\ClientTypeFactory::new();
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

    protected $table = 'mst_client_type';

    protected $fillable = [
        'client_type',
        'created_by',
        'updated_by',
    ];

    /**
     * Relationship with Clients
     */
    public function clients()
    {
        return $this->hasMany(Client::class, 'mst_client_type_id');
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientType extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\ClientTypeFactory::new();
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


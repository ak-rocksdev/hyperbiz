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

    protected $table = 'mst_client';

    protected $fillable = [
        'client_name',
        'mst_address_id',
        'client_phone_number',
        'email',
        'contact_person',
        'contact_person_phone_number',
        'mst_client_type_id',
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
}


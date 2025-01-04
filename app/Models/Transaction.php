<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Traits\LogsSystemChanges;

class Transaction extends Model
{
    use LogsSystemChanges;
    
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
        'transaction_code',
        'transaction_type',
        'mst_client_id',
        'transaction_date',
        'grand_total',
        'expedition_fee',
        'status',
        'created_by',
        'updated_by',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'mst_client_id');
    }

    public function details()
    {
        return $this->hasMany(TransactionDetail::class, 'transaction_id');
    }
}

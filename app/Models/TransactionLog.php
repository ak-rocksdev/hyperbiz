<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsSystemChanges;

class TransactionLog extends Model
{
    use HasFactory, LogsSystemChanges;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'transactions_log';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'transaction_id',
        'action',
        'changed_fields',
        'user_id',
        'actor_role',
        'ip_address',
        'user_agent',
        'action_timestamp',
        'remarks',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'changed_fields' => 'array',
        'action_timestamp' => 'datetime',
    ];
}

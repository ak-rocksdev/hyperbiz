<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemLog extends Model
{
    protected $fillable = [
        'model_type',
        'model_id',
        'user_id',
        'action',
        'changed_fields',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'changed_fields' => 'array', // Ensure changed_fields is cast as an array
    ];

    /**
     * Get the related model instance.
     */
    public function model()
    {
        return $this->morphTo();
    }

    /**
     * Get the user associated with the log.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

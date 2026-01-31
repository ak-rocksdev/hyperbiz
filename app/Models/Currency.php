<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Currency extends Model
{
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

    protected $table = 'mst_currencies';

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'exchange_rate',
        'is_base',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:6',
        'is_base' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the base currency.
     */
    public static function getBase()
    {
        return static::where('is_base', true)->first();
    }

    /**
     * Convert amount to base currency.
     */
    public function toBaseCurrency($amount)
    {
        if ($this->is_base) {
            return $amount;
        }
        return $amount * $this->exchange_rate;
    }

    /**
     * Convert amount from base currency.
     */
    public function fromBaseCurrency($amount)
    {
        if ($this->is_base) {
            return $amount;
        }
        return $amount / $this->exchange_rate;
    }
}

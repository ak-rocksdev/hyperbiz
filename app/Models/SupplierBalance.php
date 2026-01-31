<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierBalance extends Model
{
    protected $table = 'fin_supplier_balances';

    protected $fillable = [
        'supplier_id',
        'currency_code',
        'total_purchases',
        'total_payments',
        'current_balance',
        'current_0_30',
        'current_31_60',
        'current_61_90',
        'current_over_90',
        'last_purchase_date',
        'last_payment_date',
    ];

    protected $casts = [
        'total_purchases' => 'decimal:2',
        'total_payments' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'current_0_30' => 'decimal:2',
        'current_31_60' => 'decimal:2',
        'current_61_90' => 'decimal:2',
        'current_over_90' => 'decimal:2',
        'last_purchase_date' => 'date',
        'last_payment_date' => 'date',
    ];

    /**
     * Supplier relationship (uses Customer model as suppliers are in mst_client)
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'supplier_id');
    }

    /**
     * Scope by supplier
     */
    public function scopeForSupplier($query, int $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    /**
     * Scope by currency
     */
    public function scopeForCurrency($query, string $currency = 'IDR')
    {
        return $query->where('currency_code', $currency);
    }

    /**
     * Scope for suppliers with outstanding balance
     */
    public function scopeWithBalance($query)
    {
        return $query->where('current_balance', '>', 0);
    }

    /**
     * Scope for suppliers with overdue amounts
     */
    public function scopeWithOverdue($query)
    {
        return $query->where(function ($q) {
            $q->where('current_31_60', '>', 0)
                ->orWhere('current_61_90', '>', 0)
                ->orWhere('current_over_90', '>', 0);
        });
    }

    /**
     * Get or create balance record for supplier
     */
    public static function getOrCreate(int $supplierId, string $currency = 'IDR'): self
    {
        return static::firstOrCreate([
            'supplier_id' => $supplierId,
            'currency_code' => $currency,
        ], [
            'total_purchases' => 0,
            'total_payments' => 0,
            'current_balance' => 0,
            'current_0_30' => 0,
            'current_31_60' => 0,
            'current_61_90' => 0,
            'current_over_90' => 0,
        ]);
    }

    /**
     * Get total overdue amount
     */
    public function getTotalOverdueAttribute(): float
    {
        return $this->current_31_60 + $this->current_61_90 + $this->current_over_90;
    }

    /**
     * Get aging status
     */
    public function getAgingStatusAttribute(): string
    {
        if ($this->current_over_90 > 0) {
            return 'critical';
        } elseif ($this->current_61_90 > 0) {
            return 'warning';
        } elseif ($this->current_31_60 > 0) {
            return 'attention';
        } elseif ($this->current_0_30 > 0) {
            return 'current';
        }

        return 'none';
    }

    /**
     * Get aging status label
     */
    public function getAgingStatusLabelAttribute(): string
    {
        return match ($this->aging_status) {
            'critical' => 'Over 90 Days',
            'warning' => '61-90 Days',
            'attention' => '31-60 Days',
            'current' => 'Current',
            default => 'No Balance',
        };
    }

    /**
     * Get aging status color
     */
    public function getAgingStatusColorAttribute(): string
    {
        return match ($this->aging_status) {
            'critical' => 'danger',
            'warning' => 'warning',
            'attention' => 'info',
            'current' => 'success',
            default => 'light',
        };
    }
}

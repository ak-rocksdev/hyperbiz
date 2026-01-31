<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerBalance extends Model
{
    protected $table = 'fin_customer_balances';

    protected $fillable = [
        'customer_id',
        'currency_code',
        'total_sales',
        'total_payments',
        'current_balance',
        'current_0_30',
        'current_31_60',
        'current_61_90',
        'current_over_90',
        'credit_limit',
        'available_credit',
        'last_sale_date',
        'last_payment_date',
        'avg_days_to_pay',
    ];

    protected $casts = [
        'total_sales' => 'decimal:2',
        'total_payments' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'current_0_30' => 'decimal:2',
        'current_31_60' => 'decimal:2',
        'current_61_90' => 'decimal:2',
        'current_over_90' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'available_credit' => 'decimal:2',
        'avg_days_to_pay' => 'decimal:2',
        'last_sale_date' => 'date',
        'last_payment_date' => 'date',
    ];

    /**
     * Customer relationship
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Scope by customer
     */
    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope by currency
     */
    public function scopeForCurrency($query, string $currency = 'IDR')
    {
        return $query->where('currency_code', $currency);
    }

    /**
     * Scope for customers with outstanding balance
     */
    public function scopeWithBalance($query)
    {
        return $query->where('current_balance', '>', 0);
    }

    /**
     * Scope for customers with overdue amounts
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
     * Get or create balance record for customer
     */
    public static function getOrCreate(int $customerId, string $currency = 'IDR'): self
    {
        return static::firstOrCreate([
            'customer_id' => $customerId,
            'currency_code' => $currency,
        ], [
            'total_sales' => 0,
            'total_payments' => 0,
            'current_balance' => 0,
            'current_0_30' => 0,
            'current_31_60' => 0,
            'current_61_90' => 0,
            'current_over_90' => 0,
            'credit_limit' => 0,
            'available_credit' => 0,
        ]);
    }

    /**
     * Update available credit
     */
    public function updateAvailableCredit(): void
    {
        $this->available_credit = max(0, $this->credit_limit - $this->current_balance);
        $this->save();
    }

    /**
     * Check if customer has exceeded credit limit
     */
    public function hasExceededCreditLimit(): bool
    {
        if ($this->credit_limit <= 0) {
            return false; // No credit limit set
        }

        return $this->current_balance > $this->credit_limit;
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

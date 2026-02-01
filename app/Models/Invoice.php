<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\LogsSystemChanges;

class Invoice extends Model
{
    use HasFactory, LogsSystemChanges;

    protected $table = 'fin_invoices';

    protected $fillable = [
        'company_id',
        'subscription_plan_id',
        'invoice_number',
        'amount',
        'currency',
        'billing_cycle',
        'billing_period_start',
        'billing_period_end',
        'status',
        'paid_at',
        'due_date',
        'payment_method',
        'payment_reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'billing_period_start' => 'date',
            'billing_period_end' => 'date',
            'due_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Invoice status constants.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_AWAITING_VERIFICATION = 'awaiting_verification';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';

    /**
     * Payment method constants.
     */
    public const PAYMENT_STRIPE = 'stripe';
    public const PAYMENT_BANK_TRANSFER = 'bank_transfer';

    /**
     * Billing cycle constants.
     */
    public const CYCLE_MONTHLY = 'monthly';
    public const CYCLE_YEARLY = 'yearly';

    /**
     * Get the company that owns this invoice.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the subscription plan for this invoice.
     */
    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    /**
     * Get the payment transactions for this invoice.
     */
    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    /**
     * Get the latest payment transaction.
     */
    public function latestTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'id', 'invoice_id')
            ->latest();
    }

    /**
     * Check if invoice is pending payment.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if invoice is awaiting verification (bank transfer).
     */
    public function isAwaitingVerification(): bool
    {
        return $this->status === self::STATUS_AWAITING_VERIFICATION;
    }

    /**
     * Check if invoice is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Check if invoice is expired.
     */
    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    /**
     * Check if invoice is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->isPending() && $this->due_date->isPast();
    }

    /**
     * Get formatted amount with currency.
     */
    public function getFormattedAmountAttribute(): string
    {
        if ($this->currency === 'IDR') {
            return 'Rp ' . number_format($this->amount, 0, ',', '.');
        }

        return $this->currency . ' ' . number_format($this->amount, 2);
    }

    /**
     * Get status label for display.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_AWAITING_VERIFICATION => 'Awaiting Verification',
            self::STATUS_PAID => 'Paid',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_EXPIRED => 'Expired',
            default => 'Unknown',
        };
    }

    /**
     * Get status badge class for display.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'badge-warning',
            self::STATUS_AWAITING_VERIFICATION => 'badge-info',
            self::STATUS_PAID => 'badge-success',
            self::STATUS_FAILED => 'badge-danger',
            self::STATUS_CANCELLED => 'badge-secondary',
            self::STATUS_EXPIRED => 'badge-dark',
            default => 'badge-secondary',
        };
    }

    /**
     * Scope to filter by company.
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter unpaid invoices.
     */
    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_AWAITING_VERIFICATION]);
    }

    /**
     * Generate unique invoice number.
     */
    public static function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $year = date('Y');
        $month = date('m');

        $lastInvoice = self::where('invoice_number', 'like', "{$prefix}-{$year}{$month}-%")
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -5);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('%s-%s%s-%05d', $prefix, $year, $month, $newNumber);
    }
}

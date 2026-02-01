<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Traits\LogsSystemChanges;

class PaymentTransaction extends Model
{
    use HasFactory, LogsSystemChanges;

    protected $table = 'fin_payment_transactions';

    protected $fillable = [
        'company_id',
        'invoice_id',
        'transaction_id',
        'amount',
        'currency',
        'payment_method',
        'status',
        'gateway_response',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'gateway_response' => 'array',
        ];
    }

    /**
     * Transaction status constants.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_AWAITING_VERIFICATION = 'awaiting_verification';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REJECTED = 'rejected';

    /**
     * Payment method constants.
     */
    public const METHOD_STRIPE = 'stripe';
    public const METHOD_BANK_TRANSFER = 'bank_transfer';

    /**
     * Get the company that owns this transaction.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the invoice for this transaction.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the payment proof for this transaction (bank transfer only).
     */
    public function paymentProof(): HasOne
    {
        return $this->hasOne(PaymentProof::class);
    }

    /**
     * Check if transaction is successful.
     */
    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    /**
     * Check if transaction is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if transaction is awaiting verification.
     */
    public function isAwaitingVerification(): bool
    {
        return $this->status === self::STATUS_AWAITING_VERIFICATION;
    }

    /**
     * Check if transaction is failed.
     */
    public function isFailed(): bool
    {
        return in_array($this->status, [self::STATUS_FAILED, self::STATUS_EXPIRED, self::STATUS_REJECTED]);
    }

    /**
     * Check if this is a Stripe payment.
     */
    public function isStripePayment(): bool
    {
        return $this->payment_method === self::METHOD_STRIPE;
    }

    /**
     * Check if this is a bank transfer payment.
     */
    public function isBankTransfer(): bool
    {
        return $this->payment_method === self::METHOD_BANK_TRANSFER;
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
            self::STATUS_SUCCESS => 'Success',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_REJECTED => 'Rejected',
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
            self::STATUS_SUCCESS => 'badge-success',
            self::STATUS_FAILED => 'badge-danger',
            self::STATUS_EXPIRED => 'badge-dark',
            self::STATUS_REJECTED => 'badge-danger',
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
     * Scope to get successful transactions.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    /**
     * Generate unique transaction reference.
     */
    public static function generateTransactionId(): string
    {
        return 'TXN-' . strtoupper(uniqid()) . '-' . time();
    }
}

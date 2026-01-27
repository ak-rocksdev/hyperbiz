<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Auth;

class Payment extends Model
{
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_by = Auth::id();
            if (empty($model->payment_number)) {
                $model->payment_number = static::generateNumber();
            }
        });

        static::saved(function ($model) {
            // Update payment status on parent record
            $model->updateParentPaymentStatus();
        });
    }

    protected $table = 'payments';

    protected $fillable = [
        'payment_number',
        'payment_type',
        'reference_type',
        'reference_id',
        'payment_date',
        'currency_code',
        'exchange_rate',
        'amount',
        'amount_in_base',
        'payment_method',
        'bank_name',
        'account_number',
        'reference_number',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'exchange_rate' => 'decimal:6',
        'amount' => 'decimal:2',
        'amount_in_base' => 'decimal:2',
    ];

    const TYPE_PURCHASE = 'purchase';
    const TYPE_SALES = 'sales';

    const METHOD_CASH = 'cash';
    const METHOD_BANK_TRANSFER = 'bank_transfer';
    const METHOD_CREDIT_CARD = 'credit_card';
    const METHOD_DEBIT_CARD = 'debit_card';
    const METHOD_CHEQUE = 'cheque';
    const METHOD_GIRO = 'giro';
    const METHOD_E_WALLET = 'e_wallet';
    const METHOD_OTHER = 'other';

    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Generate payment number: PAY-YYYY-NNNNN
     */
    public static function generateNumber(): string
    {
        $year = date('Y');
        $prefix = "PAY-{$year}-";

        $last = static::where('payment_number', 'like', "{$prefix}%")
            ->orderBy('payment_number', 'desc')
            ->first();

        if ($last) {
            $lastNumber = (int) substr($last->payment_number, -5);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Created by user.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the parent model (PO or SO).
     */
    public function getParentAttribute()
    {
        if ($this->reference_type === 'purchase_order') {
            return PurchaseOrder::find($this->reference_id);
        } elseif ($this->reference_type === 'sales_order') {
            return SalesOrder::find($this->reference_id);
        }
        return null;
    }

    /**
     * Update payment status on parent record.
     */
    protected function updateParentPaymentStatus(): void
    {
        $parent = $this->parent;
        if ($parent) {
            $parent->updatePaymentStatus();
        }
    }

    /**
     * Get payment method label.
     */
    public function getPaymentMethodLabelAttribute(): string
    {
        return match($this->payment_method) {
            self::METHOD_CASH => 'Cash',
            self::METHOD_BANK_TRANSFER => 'Bank Transfer',
            self::METHOD_CREDIT_CARD => 'Credit Card',
            self::METHOD_DEBIT_CARD => 'Debit Card',
            self::METHOD_CHEQUE => 'Cheque',
            self::METHOD_GIRO => 'Giro',
            self::METHOD_E_WALLET => 'E-Wallet',
            self::METHOD_OTHER => 'Other',
            default => $this->payment_method,
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Traits\BelongsToCompany;

class Payment extends Model
{
    use BelongsToCompany;
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_by = Auth::id();
            if (empty($model->payment_number)) {
                $model->payment_number = static::generateNumberWithLock();
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
     * Generate payment number with database lock to prevent duplicates: PAY-YYYY-NNNNN
     */
    public static function generateNumberWithLock(): string
    {
        $year = date('Y');
        $prefix = "PAY-{$year}-";

        // Use MAX with numeric extraction to get the highest number reliably
        // This handles mixed formats like PAY-2026-00001, PAY-2026-S00001, etc.
        $result = DB::table('payments')
            ->where('payment_number', 'like', "{$prefix}%")
            ->lockForUpdate()
            ->selectRaw("MAX(CAST(REGEXP_SUBSTR(payment_number, '[0-9]+$') AS UNSIGNED)) as max_num")
            ->first();

        $newNumber = ($result->max_num ?? 0) + 1;

        return $prefix . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Generate payment number (legacy, use generateNumberWithLock instead)
     */
    public static function generateNumber(): string
    {
        return static::generateNumberWithLock();
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

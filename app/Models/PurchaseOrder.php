<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Traits\LogsSystemChanges;

class PurchaseOrder extends Model
{
    use LogsSystemChanges;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_by = Auth::id();
            if (empty($model->po_number)) {
                $model->po_number = static::generateNumber();
            }
        });

        static::updating(function ($model) {
            $model->updated_by = Auth::id();
        });
    }

    protected $table = 'purchase_orders';

    protected $fillable = [
        'po_number',
        'supplier_id',
        'order_date',
        'expected_date',
        'status',
        'currency_code',
        'exchange_rate',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_amount',
        'tax_enabled',
        'tax_name',
        'tax_percentage',
        'tax_amount',
        'grand_total',
        'payment_terms',
        'payment_status',
        'amount_paid',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_date' => 'date',
        'exchange_rate' => 'decimal:6',
        'subtotal' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_enabled' => 'boolean',
        'tax_percentage' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_PARTIAL = 'partial';
    const STATUS_RECEIVED = 'received';
    const STATUS_CANCELLED = 'cancelled';

    const PAYMENT_UNPAID = 'unpaid';
    const PAYMENT_PARTIAL = 'partial';
    const PAYMENT_PAID = 'paid';

    /**
     * Generate PO number: PO-YYYY-NNNNN
     */
    public static function generateNumber(): string
    {
        $year = date('Y');
        $prefix = "PO-{$year}-";

        $lastPo = static::where('po_number', 'like', "{$prefix}%")
            ->orderBy('po_number', 'desc')
            ->first();

        if ($lastPo) {
            $lastNumber = (int) substr($lastPo->po_number, -5);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Supplier for this PO.
     */
    public function supplier()
    {
        return $this->belongsTo(Customer::class, 'supplier_id');
    }

    /**
     * Line items.
     */
    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * Receiving records.
     */
    public function receivings()
    {
        return $this->hasMany(PurchaseReceiving::class);
    }

    /**
     * Payments for this PO.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class, 'reference_id')
            ->where('reference_type', 'purchase_order');
    }

    /**
     * Created by user.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Updated by user.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Calculate and update totals.
     */
    public function calculateTotals(): self
    {
        $this->subtotal = $this->items->sum('subtotal');

        // Calculate discount
        if ($this->discount_type === 'percentage') {
            $this->discount_amount = $this->subtotal * ($this->discount_value / 100);
        } else {
            $this->discount_amount = $this->discount_value;
        }

        $afterDiscount = $this->subtotal - $this->discount_amount;

        // Calculate tax
        if ($this->tax_enabled) {
            $this->tax_amount = $afterDiscount * ($this->tax_percentage / 100);
        } else {
            $this->tax_amount = 0;
        }

        $this->grand_total = $afterDiscount + $this->tax_amount;
        $this->save();

        return $this;
    }

    /**
     * Update payment status based on payments.
     */
    public function updatePaymentStatus(): self
    {
        $this->amount_paid = $this->payments()->where('status', 'confirmed')->sum('amount');

        if ($this->amount_paid >= $this->grand_total) {
            $this->payment_status = self::PAYMENT_PAID;
        } elseif ($this->amount_paid > 0) {
            $this->payment_status = self::PAYMENT_PARTIAL;
        } else {
            $this->payment_status = self::PAYMENT_UNPAID;
        }

        $this->save();
        return $this;
    }

    /**
     * Check if fully received.
     */
    public function isFullyReceived(): bool
    {
        foreach ($this->items as $item) {
            if ($item->quantity_received < $item->quantity) {
                return false;
            }
        }
        return true;
    }

    /**
     * Update status based on receiving.
     */
    public function updateReceivingStatus(): self
    {
        if ($this->isFullyReceived()) {
            $this->status = self::STATUS_RECEIVED;
        } elseif ($this->items->sum('quantity_received') > 0) {
            $this->status = self::STATUS_PARTIAL;
        }
        $this->save();
        return $this;
    }

    /**
     * Get amount due.
     */
    public function getAmountDueAttribute(): float
    {
        return $this->grand_total - $this->amount_paid;
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_PARTIAL => 'Partial',
            self::STATUS_RECEIVED => 'Received',
            self::STATUS_CANCELLED => 'Cancelled',
        ];

        return $labels[$this->status] ?? ucfirst($this->status ?? 'Unknown');
    }

    /**
     * Get payment status label.
     */
    public function getPaymentStatusLabelAttribute(): string
    {
        $labels = [
            self::PAYMENT_UNPAID => 'Unpaid',
            self::PAYMENT_PARTIAL => 'Partial',
            self::PAYMENT_PAID => 'Paid',
        ];

        return $labels[$this->payment_status] ?? ucfirst($this->payment_status ?? 'Unknown');
    }
}

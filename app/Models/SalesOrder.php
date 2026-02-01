<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Traits\LogsSystemChanges;
use App\Traits\BelongsToCompany;

class SalesOrder extends Model
{
    use LogsSystemChanges, BelongsToCompany;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_by = Auth::id();
            if (empty($model->so_number)) {
                $model->so_number = static::generateNumber();
            }
        });

        static::updating(function ($model) {
            $model->updated_by = Auth::id();
        });
    }

    protected $table = 'sales_orders';

    protected $fillable = [
        'so_number',
        'customer_id',
        'order_date',
        'due_date',
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
        'shipping_fee',
        'shipping_address',
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
        'due_date' => 'date',
        'exchange_rate' => 'decimal:6',
        'subtotal' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_enabled' => 'boolean',
        'tax_percentage' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_PROCESSING = 'processing';
    const STATUS_PARTIAL = 'partial';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';

    const PAYMENT_UNPAID = 'unpaid';
    const PAYMENT_PARTIAL = 'partial';
    const PAYMENT_PAID = 'paid';

    /**
     * Generate SO number: SO-YYYY-NNNNN
     */
    public static function generateNumber(): string
    {
        $year = date('Y');
        $prefix = "SO-{$year}-";

        $lastSo = static::where('so_number', 'like', "{$prefix}%")
            ->orderBy('so_number', 'desc')
            ->first();

        if ($lastSo) {
            $lastNumber = (int) substr($lastSo->so_number, -5);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Customer for this SO.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Line items.
     */
    public function items()
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    /**
     * Shipments.
     */
    public function shipments()
    {
        return $this->hasMany(SalesShipment::class);
    }

    /**
     * Payments for this SO.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class, 'reference_id')
            ->where('reference_type', 'sales_order');
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

        $this->grand_total = $afterDiscount + $this->tax_amount + $this->shipping_fee;
        $this->save();

        return $this;
    }

    /**
     * Update payment status.
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
     * Confirm order (reserve stock).
     */
    public function confirm(): self
    {
        if ($this->status !== self::STATUS_DRAFT) {
            return $this;
        }

        foreach ($this->items as $item) {
            $stock = InventoryStock::getOrCreate($item->product_id);
            if (!$stock->hasAvailable($item->quantity)) {
                throw new \Exception("Insufficient stock for product: {$item->product->name}");
            }
            $stock->reserveStock($item->quantity);
        }

        $this->status = self::STATUS_CONFIRMED;
        $this->save();

        return $this;
    }

    /**
     * Mark as delivered (deduct stock, calculate COGS).
     */
    public function markAsDelivered(): self
    {
        foreach ($this->items as $item) {
            $stock = InventoryStock::getOrCreate($item->product_id);

            // Release reservation
            $stock->releaseReserved($item->quantity);

            // Record movement (deduct stock)
            InventoryMovement::record(
                $item->product_id,
                InventoryMovement::TYPE_SALES_OUT,
                $item->quantity,
                'sales_order',
                $this->id,
                $stock->average_cost, // Use average cost for COGS
                "Sold via SO: {$this->so_number}"
            );

            // Store COGS in item
            $item->unit_cost = $stock->average_cost;
            $item->save();
        }

        $this->status = self::STATUS_DELIVERED;
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
     * Get gross profit.
     */
    public function getGrossProfitAttribute(): float
    {
        $revenue = $this->subtotal - $this->discount_amount;
        $cogs = $this->items->sum(function ($item) {
            return $item->quantity * ($item->unit_cost ?? 0);
        });
        return $revenue - $cogs;
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_PARTIAL => 'Partial',
            self::STATUS_SHIPPED => 'Shipped',
            self::STATUS_DELIVERED => 'Delivered',
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Traits\LogsSystemChanges;
use App\Traits\BelongsToCompany;

class SalesReturn extends Model
{
    use LogsSystemChanges, BelongsToCompany;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_by = Auth::id();
            if (empty($model->return_number)) {
                $model->return_number = static::generateNumber();
            }
        });

        static::updating(function ($model) {
            $model->updated_by = Auth::id();
        });
    }

    protected $table = 'sales_returns';

    protected $fillable = [
        'return_number',
        'sales_order_id',
        'customer_id',
        'return_date',
        'currency_code',
        'exchange_rate',
        'status',
        'subtotal',
        'refund_method',
        'reason',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'return_date' => 'date',
        'exchange_rate' => 'decimal:6',
        'subtotal' => 'decimal:2',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_COMPLETED = 'completed';

    const REFUND_CREDIT_NOTE = 'credit_note';
    const REFUND_CASH = 'cash_refund';
    const REFUND_REPLACEMENT = 'replacement';
    const REFUND_NONE = 'none';

    /**
     * Generate return number: SR-YYYY-NNNNN
     */
    public static function generateNumber(): string
    {
        $year = date('Y');
        $prefix = "SR-{$year}-";

        $last = static::where('return_number', 'like', "{$prefix}%")
            ->orderBy('return_number', 'desc')
            ->first();

        if ($last) {
            $lastNumber = (int) substr($last->return_number, -5);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Sales order this return is for.
     */
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    /**
     * Customer for this return.
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
        return $this->hasMany(SalesReturnItem::class);
    }

    /**
     * Created by user.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Calculate subtotal.
     */
    public function calculateSubtotal(): self
    {
        $this->subtotal = $this->items->sum('subtotal');
        $this->save();
        return $this;
    }

    /**
     * Confirm return (add stock back for good items).
     */
    public function confirm(): self
    {
        if ($this->status !== self::STATUS_DRAFT) {
            return $this;
        }

        foreach ($this->items as $item) {
            // Only restock items in good condition that are marked for restocking
            if ($item->restock && $item->condition === 'good') {
                InventoryMovement::record(
                    $item->product_id,
                    InventoryMovement::TYPE_SALES_RETURN,
                    $item->quantity,
                    'sales_return',
                    $this->id,
                    $item->unit_cost,
                    "Returned by customer: {$this->customer->client_name}"
                );
            }
        }

        $this->status = self::STATUS_CONFIRMED;
        $this->save();

        return $this;
    }

    /**
     * Get refund method label.
     */
    public function getRefundMethodLabelAttribute(): string
    {
        return match($this->refund_method) {
            self::REFUND_CREDIT_NOTE => 'Credit Note',
            self::REFUND_CASH => 'Cash Refund',
            self::REFUND_REPLACEMENT => 'Replacement',
            self::REFUND_NONE => 'No Refund',
            default => $this->refund_method ?? 'N/A',
        };
    }
}

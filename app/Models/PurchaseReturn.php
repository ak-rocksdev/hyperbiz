<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Traits\LogsSystemChanges;
use App\Traits\BelongsToCompany;

class PurchaseReturn extends Model
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

    protected $table = 'purchase_returns';

    protected $fillable = [
        'return_number',
        'purchase_order_id',
        'supplier_id',
        'return_date',
        'currency_code',
        'exchange_rate',
        'status',
        'subtotal',
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

    /**
     * Generate return number: PR-YYYY-NNNNN
     */
    public static function generateNumber(): string
    {
        $year = date('Y');
        $prefix = "PR-{$year}-";

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
     * Purchase order this return is for.
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Supplier for this return.
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
        return $this->hasMany(PurchaseReturnItem::class);
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
     * Confirm return (deduct stock).
     */
    public function confirm(): self
    {
        if ($this->status !== self::STATUS_DRAFT) {
            return $this;
        }

        foreach ($this->items as $item) {
            InventoryMovement::record(
                $item->product_id,
                InventoryMovement::TYPE_PURCHASE_RETURN,
                $item->quantity,
                'purchase_return',
                $this->id,
                $item->unit_cost,
                "Returned to supplier: {$this->supplier->client_name}"
            );
        }

        $this->status = self::STATUS_CONFIRMED;
        $this->save();

        return $this;
    }
}

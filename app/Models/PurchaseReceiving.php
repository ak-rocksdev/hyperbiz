<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Traits\BelongsToCompany;

class PurchaseReceiving extends Model
{
    use BelongsToCompany;
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_by = Auth::id();
            if (empty($model->receiving_number)) {
                $model->receiving_number = static::generateNumber();
            }
        });
    }

    protected $table = 'purchase_receivings';

    protected $fillable = [
        'receiving_number',
        'purchase_order_id',
        'receiving_date',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'receiving_date' => 'date',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_CONFIRMED = 'confirmed';

    /**
     * Generate receiving number: RCV-YYYY-NNNNN
     */
    public static function generateNumber(): string
    {
        $year = date('Y');
        $prefix = "RCV-{$year}-";

        $last = static::where('receiving_number', 'like', "{$prefix}%")
            ->orderBy('receiving_number', 'desc')
            ->first();

        if ($last) {
            $lastNumber = (int) substr($last->receiving_number, -5);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Purchase order for this receiving.
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Line items for this receiving.
     */
    public function items()
    {
        return $this->hasMany(PurchaseReceivingItem::class);
    }

    /**
     * Created by user.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Confirm receiving and update inventory.
     */
    public function confirm(): self
    {
        if ($this->status === self::STATUS_CONFIRMED) {
            return $this;
        }

        foreach ($this->items as $item) {
            // Update PO item received quantity
            $item->purchaseOrderItem->quantity_received += $item->quantity_received;
            $item->purchaseOrderItem->save();

            // Record inventory movement
            InventoryMovement::record(
                $item->product_id,
                InventoryMovement::TYPE_PURCHASE_IN,
                $item->quantity_received,
                'purchase_receiving',
                $this->id,
                $item->unit_cost,
                "Received from PO: {$this->purchaseOrder->po_number}"
            );
        }

        $this->status = self::STATUS_CONFIRMED;
        $this->save();

        // Update PO status
        $this->purchaseOrder->updateReceivingStatus();

        return $this;
    }
}

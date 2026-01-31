<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class SalesShipment extends Model
{
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_by = Auth::id();
            if (empty($model->shipment_number)) {
                $model->shipment_number = static::generateNumber();
            }
        });
    }

    protected $table = 'sales_shipments';

    protected $fillable = [
        'shipment_number',
        'sales_order_id',
        'shipment_date',
        'courier',
        'tracking_number',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'shipment_date' => 'date',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_DELIVERED = 'delivered';

    /**
     * Generate shipment number: SHP-YYYY-NNNNN
     */
    public static function generateNumber(): string
    {
        $year = date('Y');
        $prefix = "SHP-{$year}-";

        $last = static::where('shipment_number', 'like', "{$prefix}%")
            ->orderBy('shipment_number', 'desc')
            ->first();

        if ($last) {
            $lastNumber = (int) substr($last->shipment_number, -5);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Sales order for this shipment.
     */
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    /**
     * Line items.
     */
    public function items()
    {
        return $this->hasMany(SalesShipmentItem::class);
    }

    /**
     * Created by user.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

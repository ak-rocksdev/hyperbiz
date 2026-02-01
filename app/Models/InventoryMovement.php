<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Traits\BelongsToCompany;

class InventoryMovement extends Model
{
    use BelongsToCompany;
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_by = Auth::id();
        });
    }

    protected $table = 'inventory_movements';

    protected $fillable = [
        'movement_date',
        'product_id',
        'movement_type',
        'reference_type',
        'reference_id',
        'quantity',
        'unit_cost',
        'quantity_before',
        'quantity_after',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'movement_date' => 'datetime',
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'quantity_before' => 'decimal:3',
        'quantity_after' => 'decimal:3',
    ];

    const TYPE_PURCHASE_IN = 'purchase_in';
    const TYPE_SALES_OUT = 'sales_out';
    const TYPE_PURCHASE_RETURN = 'purchase_return';
    const TYPE_SALES_RETURN = 'sales_return';
    const TYPE_ADJUSTMENT_IN = 'adjustment_in';
    const TYPE_ADJUSTMENT_OUT = 'adjustment_out';
    const TYPE_OPENING_STOCK = 'opening_stock';

    /**
     * Movement type labels for display.
     */
    public static $typeLabels = [
        self::TYPE_PURCHASE_IN => 'Purchase In',
        self::TYPE_SALES_OUT => 'Sales Out',
        self::TYPE_PURCHASE_RETURN => 'Purchase Return',
        self::TYPE_SALES_RETURN => 'Sales Return',
        self::TYPE_ADJUSTMENT_IN => 'Adjustment In',
        self::TYPE_ADJUSTMENT_OUT => 'Adjustment Out',
        self::TYPE_OPENING_STOCK => 'Opening Stock',
    ];

    /**
     * Get the movement type label.
     */
    public function getMovementTypeLabelAttribute(): string
    {
        return self::$typeLabels[$this->movement_type] ?? ucfirst(str_replace('_', ' ', $this->movement_type));
    }

    /**
     * Check if this is an incoming movement (stock increase).
     */
    public function getIsIncomingAttribute(): bool
    {
        return in_array($this->movement_type, [
            self::TYPE_PURCHASE_IN,
            self::TYPE_SALES_RETURN,
            self::TYPE_ADJUSTMENT_IN,
            self::TYPE_OPENING_STOCK,
        ]);
    }

    /**
     * Check if this is an outgoing movement (stock decrease).
     */
    public function getIsOutgoingAttribute(): bool
    {
        return in_array($this->movement_type, [
            self::TYPE_SALES_OUT,
            self::TYPE_PURCHASE_RETURN,
            self::TYPE_ADJUSTMENT_OUT,
        ]);
    }

    /**
     * Product this movement is for.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * User who created this movement.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Record a stock movement.
     */
    public static function record(
        int $productId,
        string $movementType,
        float $quantity,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?float $unitCost = null,
        ?string $notes = null
    ): self {
        $stock = InventoryStock::getOrCreate($productId);
        $quantityBefore = $stock->quantity_on_hand;

        // Determine if this is an IN or OUT movement
        $isIn = in_array($movementType, [
            self::TYPE_PURCHASE_IN,
            self::TYPE_SALES_RETURN,
            self::TYPE_ADJUSTMENT_IN,
            self::TYPE_OPENING_STOCK,
        ]);

        // Adjust stock
        if ($isIn) {
            $stock->addStock($quantity, $unitCost);
        } else {
            $stock->deductStock($quantity);
        }

        // Create movement record
        return static::create([
            'movement_date' => now(),
            'product_id' => $productId,
            'movement_type' => $movementType,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'quantity' => $isIn ? $quantity : -$quantity,
            'unit_cost' => $unitCost,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $stock->quantity_on_hand,
            'notes' => $notes,
        ]);
    }

    /**
     * Get movements for a product.
     */
    public static function getForProduct(int $productId)
    {
        return static::where('product_id', $productId)
            ->orderBy('movement_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Get FIFO cost batches for a product (for COGS calculation).
     */
    public static function getFifoBatches(int $productId)
    {
        return static::where('product_id', $productId)
            ->whereIn('movement_type', [self::TYPE_PURCHASE_IN, self::TYPE_OPENING_STOCK])
            ->where('quantity', '>', 0)
            ->orderBy('movement_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();
    }
}

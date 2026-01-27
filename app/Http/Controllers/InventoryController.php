<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\InventoryStock;
use App\Models\InventoryMovement;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * Display inventory stock list.
     */
    public function list(Request $request)
    {
        $search = $request->input('search');
        $stockStatus = $request->input('stock_status');
        $perPage = $request->input('per_page', 10);

        $query = InventoryStock::with(['product', 'product.uom'])
            ->orderBy('updated_at', 'desc');

        if ($search) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($stockStatus === 'low') {
            $query->whereRaw('quantity_available <= reorder_level AND reorder_level > 0');
        } elseif ($stockStatus === 'out') {
            $query->where('quantity_available', '<=', 0);
        } elseif ($stockStatus === 'available') {
            $query->where('quantity_available', '>', 0);
        }

        $stocks = $query->paginate($perPage);

        $data = $stocks->getCollection()->map(function ($stock) {
            return [
                'id' => $stock->id,
                'product_id' => $stock->product_id,
                'product_name' => $stock->product->name ?? 'N/A',
                'sku' => $stock->product->sku ?? 'N/A',
                'uom' => $stock->product->uom->name ?? 'PCS',
                'quantity_on_hand' => $stock->quantity_on_hand,
                'quantity_reserved' => $stock->quantity_reserved,
                'quantity_available' => $stock->quantity_available,
                'reorder_level' => $stock->reorder_level,
                'average_cost' => $stock->average_cost,
                'last_cost' => $stock->last_cost,
                'stock_value' => $stock->quantity_on_hand * $stock->average_cost,
                'last_movement_at' => $stock->last_movement_at
                    ? Carbon::parse($stock->last_movement_at)->format('d M Y H:i')
                    : 'Never',
                'status' => $this->getStockStatus($stock),
            ];
        });

        // Calculate summary stats
        $stats = [
            'total_products' => InventoryStock::count(),
            'total_stock_value' => InventoryStock::selectRaw('SUM(quantity_on_hand * average_cost) as value')->value('value') ?? 0,
            'low_stock_count' => InventoryStock::whereRaw('quantity_available <= reorder_level AND reorder_level > 0')->count(),
            'out_of_stock_count' => InventoryStock::where('quantity_available', '<=', 0)->count(),
        ];

        return Inertia::render('Inventory/List', [
            'stocks' => $data,
            'pagination' => [
                'total' => $stocks->total(),
                'per_page' => $stocks->perPage(),
                'current_page' => $stocks->currentPage(),
                'last_page' => $stocks->lastPage(),
            ],
            'stats' => $stats,
            'filters' => [
                'search' => $search,
                'stock_status' => $stockStatus,
            ],
        ]);
    }

    /**
     * Show inventory details with movement history.
     */
    public function show(Request $request, $productId)
    {
        $product = Product::with(['uom', 'inventoryStock'])->findOrFail($productId);
        $stock = $product->inventoryStock;

        if (!$stock) {
            // Initialize stock if not exists
            $stock = InventoryStock::create([
                'product_id' => $product->id,
                'quantity_on_hand' => $product->stock_quantity ?? 0,
                'quantity_reserved' => 0,
                'quantity_available' => $product->stock_quantity ?? 0,
                'last_cost' => $product->cost_price,
                'average_cost' => $product->cost_price,
            ]);
        }

        // Get movement history
        $movementQuery = InventoryMovement::where('product_id', $productId)
            ->with('createdBy')
            ->orderByDesc('movement_date')
            ->orderByDesc('created_at');

        $perPage = $request->input('per_page', 20);
        $movements = $movementQuery->paginate($perPage);

        $movementData = $movements->getCollection()->map(function ($movement) {
            return [
                'id' => $movement->id,
                'movement_number' => $movement->movement_number,
                'movement_date' => Carbon::parse($movement->movement_date)->format('d M Y'),
                'movement_type' => $movement->movement_type,
                'movement_type_label' => $movement->movement_type_label,
                'reference_type' => $movement->reference_type,
                'reference_id' => $movement->reference_id,
                'quantity' => $movement->quantity,
                'unit_cost' => $movement->unit_cost,
                'quantity_before' => $movement->quantity_before,
                'quantity_after' => $movement->quantity_after,
                'notes' => $movement->notes,
                'created_by' => $movement->createdBy->name ?? 'System',
                'created_at' => Carbon::parse($movement->created_at)->format('d M Y H:i'),
            ];
        });

        $productData = [
            'id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'uom' => $product->uom->name ?? 'PCS',
            'cost_price' => $product->cost_price,
            'selling_price' => $product->price,
        ];

        $stockData = [
            'quantity_on_hand' => $stock->quantity_on_hand,
            'quantity_reserved' => $stock->quantity_reserved,
            'quantity_available' => $stock->quantity_available,
            'reorder_level' => $stock->reorder_level,
            'average_cost' => $stock->average_cost,
            'last_cost' => $stock->last_cost,
            'stock_value' => $stock->quantity_on_hand * $stock->average_cost,
            'last_movement_at' => $stock->last_movement_at
                ? Carbon::parse($stock->last_movement_at)->format('d M Y H:i')
                : 'Never',
            'status' => $this->getStockStatus($stock),
        ];

        return Inertia::render('Inventory/Detail', [
            'product' => $productData,
            'stock' => $stockData,
            'movements' => $movementData,
            'movementPagination' => [
                'total' => $movements->total(),
                'per_page' => $movements->perPage(),
                'current_page' => $movements->currentPage(),
                'last_page' => $movements->lastPage(),
            ],
        ]);
    }

    /**
     * Get stock movements for API.
     */
    public function getMovements(Request $request, $productId)
    {
        $perPage = $request->input('per_page', 20);
        $movementType = $request->input('movement_type');

        $query = InventoryMovement::where('product_id', $productId)
            ->with('createdBy')
            ->orderByDesc('movement_date')
            ->orderByDesc('created_at');

        if ($movementType) {
            $query->where('movement_type', $movementType);
        }

        $movements = $query->paginate($perPage);

        $data = $movements->getCollection()->map(function ($movement) {
            return [
                'id' => $movement->id,
                'movement_number' => $movement->movement_number,
                'movement_date' => Carbon::parse($movement->movement_date)->format('d M Y'),
                'movement_type' => $movement->movement_type,
                'movement_type_label' => $movement->movement_type_label,
                'reference_type' => $movement->reference_type,
                'reference_id' => $movement->reference_id,
                'quantity' => $movement->quantity,
                'unit_cost' => $movement->unit_cost,
                'quantity_before' => $movement->quantity_before,
                'quantity_after' => $movement->quantity_after,
                'notes' => $movement->notes,
                'created_by' => $movement->createdBy->name ?? 'System',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'total' => $movements->total(),
                'per_page' => $movements->perPage(),
                'current_page' => $movements->currentPage(),
                'last_page' => $movements->lastPage(),
            ],
        ]);
    }

    /**
     * Create stock adjustment (add or deduct).
     */
    public function adjust(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);

        $validated = $request->validate([
            'adjustment_type' => 'required|in:add,deduct',
            'quantity' => 'required|numeric|min:0.01',
            'unit_cost' => 'nullable|numeric|min:0',
            'reason' => 'required|string|max:500',
        ]);

        DB::beginTransaction();

        try {
            $stock = $product->inventoryStock;

            if (!$stock) {
                $stock = InventoryStock::create([
                    'product_id' => $product->id,
                    'quantity_on_hand' => 0,
                    'quantity_reserved' => 0,
                    'quantity_available' => 0,
                    'last_cost' => $product->cost_price,
                    'average_cost' => $product->cost_price,
                ]);
            }

            $quantityBefore = $stock->quantity_on_hand;
            $unitCost = $validated['unit_cost'] ?? $stock->average_cost;

            if ($validated['adjustment_type'] === 'add') {
                $stock->addStock($validated['quantity'], $unitCost);
                $movementType = InventoryMovement::TYPE_ADJUSTMENT_IN;
                $quantityChange = $validated['quantity'];
            } else {
                if ($validated['quantity'] > $stock->quantity_available) {
                    return response()->json([
                        'success' => false,
                        'message' => "Cannot deduct {$validated['quantity']} units. Only {$stock->quantity_available} available.",
                    ], 422);
                }
                $stock->deductStock($validated['quantity']);
                $movementType = InventoryMovement::TYPE_ADJUSTMENT_OUT;
                $quantityChange = -$validated['quantity'];
            }

            // Record the movement
            InventoryMovement::record(
                $product->id,
                $movementType,
                'adjustment',
                null,
                $quantityChange,
                $unitCost,
                $quantityBefore,
                $stock->quantity_on_hand,
                $validated['reason']
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock adjusted successfully.',
                'data' => [
                    'quantity_on_hand' => $stock->quantity_on_hand,
                    'quantity_available' => $stock->quantity_available,
                    'average_cost' => $stock->average_cost,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to adjust stock.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Update reorder level.
     */
    public function updateReorderLevel(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);

        $validated = $request->validate([
            'reorder_level' => 'required|numeric|min:0',
        ]);

        $stock = $product->inventoryStock;

        if (!$stock) {
            $stock = InventoryStock::create([
                'product_id' => $product->id,
                'quantity_on_hand' => $product->stock_quantity ?? 0,
                'quantity_reserved' => 0,
                'quantity_available' => $product->stock_quantity ?? 0,
                'last_cost' => $product->cost_price,
                'average_cost' => $product->cost_price,
                'reorder_level' => $validated['reorder_level'],
            ]);
        } else {
            $stock->reorder_level = $validated['reorder_level'];
            $stock->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Reorder level updated.',
            'data' => [
                'reorder_level' => $stock->reorder_level,
            ],
        ]);
    }

    /**
     * Low stock report.
     */
    public function lowStockReport(Request $request)
    {
        $perPage = $request->input('per_page', 50);

        $stocks = InventoryStock::with(['product', 'product.uom'])
            ->whereRaw('quantity_available <= reorder_level AND reorder_level > 0')
            ->orderByRaw('quantity_available - reorder_level ASC')
            ->paginate($perPage);

        $data = $stocks->getCollection()->map(function ($stock) {
            return [
                'id' => $stock->id,
                'product_id' => $stock->product_id,
                'product_name' => $stock->product->name ?? 'N/A',
                'sku' => $stock->product->sku ?? 'N/A',
                'uom' => $stock->product->uom->name ?? 'PCS',
                'quantity_available' => $stock->quantity_available,
                'reorder_level' => $stock->reorder_level,
                'shortage' => $stock->reorder_level - $stock->quantity_available,
                'average_cost' => $stock->average_cost,
                'last_cost' => $stock->last_cost,
            ];
        });

        return Inertia::render('Inventory/LowStockReport', [
            'stocks' => $data,
            'pagination' => [
                'total' => $stocks->total(),
                'per_page' => $stocks->perPage(),
                'current_page' => $stocks->currentPage(),
                'last_page' => $stocks->lastPage(),
            ],
        ]);
    }

    /**
     * Stock valuation report.
     */
    public function valuationReport(Request $request)
    {
        $perPage = $request->input('per_page', 50);

        $stocks = InventoryStock::with(['product', 'product.uom'])
            ->where('quantity_on_hand', '>', 0)
            ->orderByRaw('quantity_on_hand * average_cost DESC')
            ->paginate($perPage);

        $data = $stocks->getCollection()->map(function ($stock) {
            return [
                'id' => $stock->id,
                'product_id' => $stock->product_id,
                'product_name' => $stock->product->name ?? 'N/A',
                'sku' => $stock->product->sku ?? 'N/A',
                'uom' => $stock->product->uom->name ?? 'PCS',
                'quantity_on_hand' => $stock->quantity_on_hand,
                'average_cost' => $stock->average_cost,
                'stock_value' => $stock->quantity_on_hand * $stock->average_cost,
            ];
        });

        $totalValue = InventoryStock::selectRaw('SUM(quantity_on_hand * average_cost) as value')->value('value') ?? 0;

        return Inertia::render('Inventory/ValuationReport', [
            'stocks' => $data,
            'totalValue' => $totalValue,
            'pagination' => [
                'total' => $stocks->total(),
                'per_page' => $stocks->perPage(),
                'current_page' => $stocks->currentPage(),
                'last_page' => $stocks->lastPage(),
            ],
        ]);
    }

    /**
     * Get stock status label.
     */
    private function getStockStatus($stock): string
    {
        if ($stock->quantity_available <= 0) {
            return 'out_of_stock';
        }
        if ($stock->reorder_level > 0 && $stock->quantity_available <= $stock->reorder_level) {
            return 'low_stock';
        }
        return 'in_stock';
    }

    /**
     * Display all inventory movements with filters.
     */
    public function movements(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $search = $request->input('search');
        $productId = $request->input('product_id');
        $movementType = $request->input('movement_type');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = InventoryMovement::with(['product', 'createdBy'])
            ->orderByDesc('movement_date')
            ->orderByDesc('id');

        // Filter by product_id (from query param, e.g., from Product Detail page)
        if ($productId) {
            $query->where('product_id', $productId);
        }

        // Search by product name or SKU
        if ($search) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Filter by movement type
        if ($movementType) {
            $query->where('movement_type', $movementType);
        }

        // Filter by date range
        if ($dateFrom) {
            $query->whereDate('movement_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('movement_date', '<=', $dateTo);
        }

        $movements = $query->paginate($perPage);

        $data = $movements->getCollection()->map(function ($movement) {
            return [
                'id' => $movement->id,
                'movement_date' => Carbon::parse($movement->movement_date)->format('d M Y H:i'),
                'movement_date_raw' => $movement->movement_date->toDateString(),
                'product_id' => $movement->product_id,
                'product_name' => $movement->product->name ?? 'N/A',
                'product_sku' => $movement->product->sku ?? '-',
                'movement_type' => $movement->movement_type,
                'movement_type_label' => $movement->movement_type_label,
                'is_incoming' => $movement->is_incoming,
                'is_outgoing' => $movement->is_outgoing,
                'reference_type' => $movement->reference_type,
                'reference_id' => $movement->reference_id,
                'quantity' => $movement->quantity,
                'unit_cost' => $movement->unit_cost,
                'quantity_before' => $movement->quantity_before,
                'quantity_after' => $movement->quantity_after,
                'notes' => $movement->notes,
                'created_by' => $movement->createdBy->name ?? 'System',
                'created_at' => Carbon::parse($movement->created_at)->format('d M Y H:i'),
            ];
        });

        // Get product for header if filtering by product_id
        $filterProduct = null;
        if ($productId) {
            $product = Product::find($productId);
            if ($product) {
                $filterProduct = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                ];
            }
        }

        // Movement type options for filter dropdown
        $movementTypes = [
            ['value' => 'purchase_in', 'label' => 'Purchase In'],
            ['value' => 'sales_out', 'label' => 'Sales Out'],
            ['value' => 'purchase_return', 'label' => 'Purchase Return'],
            ['value' => 'sales_return', 'label' => 'Sales Return'],
            ['value' => 'adjustment_in', 'label' => 'Adjustment In'],
            ['value' => 'adjustment_out', 'label' => 'Adjustment Out'],
            ['value' => 'opening_stock', 'label' => 'Opening Stock'],
        ];

        // Summary stats
        $statsQuery = InventoryMovement::query();
        if ($productId) {
            $statsQuery->where('product_id', $productId);
        }

        $stats = [
            'total_movements' => $statsQuery->count(),
            'total_in' => (clone $statsQuery)->where('quantity', '>', 0)->sum('quantity'),
            'total_out' => abs((clone $statsQuery)->where('quantity', '<', 0)->sum('quantity')),
        ];

        return Inertia::render('Inventory/Movements', [
            'movements' => $data,
            'pagination' => [
                'total' => $movements->total(),
                'per_page' => $movements->perPage(),
                'current_page' => $movements->currentPage(),
                'last_page' => $movements->lastPage(),
                'from' => $movements->firstItem(),
                'to' => $movements->lastItem(),
            ],
            'filters' => [
                'search' => $search,
                'product_id' => $productId,
                'movement_type' => $movementType,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'filterProduct' => $filterProduct,
            'movementTypes' => $movementTypes,
            'stats' => $stats,
        ]);
    }
}

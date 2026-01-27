<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\InventoryStock;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Number;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display a list of products.
     */
    public function list(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');
        $categoryId = $request->get('category_id');
        $brandId = $request->get('brand_id');
        $stockStatus = $request->get('stock_status');

        $query = Product::with(['category', 'brand', 'customer', 'inventoryStock'])
            ->orderByDesc('created_at');

        // Search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($categoryId) {
            $query->where('mst_product_category_id', $categoryId);
        }

        // Brand filter
        if ($brandId) {
            $query->where('mst_brand_id', $brandId);
        }

        // Stock status filter (applied BEFORE pagination using subquery)
        if ($stockStatus) {
            switch ($stockStatus) {
                case 'out_of_stock':
                    // quantity_on_hand <= 0
                    $query->whereHas('inventoryStock', function ($q) {
                        $q->where('quantity_on_hand', '<=', 0);
                    });
                    break;
                case 'low_stock':
                    // reorder_level > 0 AND quantity_on_hand <= reorder_level AND quantity_on_hand > 0
                    $query->whereHas('inventoryStock', function ($q) {
                        $q->where('reorder_level', '>', 0)
                            ->whereColumn('quantity_on_hand', '<=', 'reorder_level')
                            ->where('quantity_on_hand', '>', 0);
                    });
                    break;
                case 'in_stock':
                    // quantity_on_hand > 0 AND (reorder_level = 0 OR quantity_on_hand > reorder_level)
                    $query->whereHas('inventoryStock', function ($q) {
                        $q->where('quantity_on_hand', '>', 0)
                            ->where(function ($subQ) {
                                $subQ->where('reorder_level', '<=', 0)
                                    ->orWhereColumn('quantity_on_hand', '>', 'reorder_level');
                            });
                    });
                    break;
                case 'no_stock':
                    // No inventory stock record
                    $query->doesntHave('inventoryStock');
                    break;
            }
        }

        // Paginate
        $paginator = $query->paginate($perPage);

        // Map products
        $data = $paginator->getCollection()->map(function ($product) {
            $stock = $product->inventoryStock;
            return [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'price' => $product->price,
                'currency' => $product->currency,
                'category' => $product->category->name ?? 'N/A',
                'category_id' => $product->mst_product_category_id,
                'brand' => $product->brand->name ?? 'N/A',
                'brand_id' => $product->mst_brand_id,
                'customer' => $product->customer->client_name ?? 'N/A',
                'weight' => $product->weight,
                'dimensions' => $product->dimensions,
                'image_url' => $product->image_url,
                'created_at' => Carbon::parse($product->created_at)->format('d M Y'),
                'is_active' => $product->is_active,
                // Inventory Stock data (single source of truth)
                'quantity_on_hand' => $stock->quantity_on_hand ?? 0,
                'quantity_available' => $stock->quantity_available ?? 0,
                'quantity_reserved' => $stock->quantity_reserved ?? 0,
                'reorder_level' => $stock->reorder_level ?? 0,
                'average_cost' => $stock->average_cost ?? $product->cost_price ?? 0,
                'stock_status' => $product->stock_status,
                'is_low_stock' => $product->is_low_stock,
            ];
        });

        // Stats (calculate from all products, not just paginated)
        $allProducts = Product::with('inventoryStock')->get();
        $totalProducts = $allProducts->count();
        $totalCategoriesCount = Product::distinct('mst_product_category_id')->count('mst_product_category_id');
        $lowStockCount = $allProducts->filter(fn($p) => $p->is_low_stock)->count();
        $outOfStockCount = $allProducts->filter(fn($p) => $p->stock_status === 'out_of_stock')->count();

        // Format categories and brands for SearchableSelect
        $categories = ProductCategory::orderBy('name')->get()->map(fn($c) => [
            'value' => $c->id,
            'label' => $c->name,
        ]);
        $brands = Brand::orderBy('name')->get()->map(fn($b) => [
            'value' => $b->id,
            'label' => $b->name,
        ]);
        $customers = Customer::orderBy('client_name')->get()->map(fn($c) => [
            'value' => $c->id,
            'label' => $c->client_name,
        ]);

        // Stock status options
        $stockStatusOptions = [
            ['value' => 'in_stock', 'label' => 'In Stock'],
            ['value' => 'low_stock', 'label' => 'Low Stock'],
            ['value' => 'out_of_stock', 'label' => 'Out of Stock'],
            ['value' => 'no_stock', 'label' => 'No Stock'],
        ];

        return Inertia::render('Product/List', [
            'products' => $data,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'filters' => [
                'search' => $search,
                'category_id' => $categoryId,
                'brand_id' => $brandId,
                'stock_status' => $stockStatus,
            ],
            'customers' => $customers,
            'categories' => $categories,
            'brands' => $brands,
            'stockStatusOptions' => $stockStatusOptions,
            'stats' => [
                'total_products' => $totalProducts,
                'total_categories' => $totalCategoriesCount,
                'low_stock' => $lowStockCount,
                'out_of_stock' => $outOfStockCount,
            ],
        ]);
    }

    /**
     * Fetch product details via API.
     */
    public function detailApi($id)
    {
        $product = Product::with(['category', 'brand', 'customer', 'inventoryStock'])->findOrFail($id);
        $stock = $product->inventoryStock;

        return response()->json([
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'sku' => $product->sku ?? 'N/A',
                'barcode' => $product->barcode ?? 'N/A',
                'price' => $product->price ?? 0,
                'currency' => $product->currency,
                'category' => $product->category->name ?? 'N/A',
                'brand' => $product->brand->name ?? 'N/A',
                'customer' => $product->customer->client_name ?? 'N/A',
                'weight' => $product->weight ?? 0,
                'dimensions' => $product->dimensions ?? 'N/A',
                'image_url' => $product->image_url,
                'is_active' => $product->is_active,
                'created_at' => Carbon::parse($product->created_at)->format('d M Y - H:i'),
                // Inventory Stock data (single source of truth)
                'quantity_on_hand' => $stock->quantity_on_hand ?? 0,
                'quantity_available' => $stock->quantity_available ?? 0,
                'quantity_reserved' => $stock->quantity_reserved ?? 0,
                'reorder_level' => $stock->reorder_level ?? 0,
                'average_cost' => $stock->average_cost ?? $product->cost_price ?? 0,
                'last_cost' => $stock->last_cost ?? 0,
                'stock_status' => $product->stock_status,
                'is_low_stock' => $product->is_low_stock,
            ],
        ]);
    }

    /**
     * Store a new product.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sku' => 'nullable|string|max:100|unique:mst_products,sku',
            'barcode' => 'nullable|string|max:100',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'mst_product_category_id' => 'nullable|exists:mst_product_categories,id',
            'mst_brand_id' => 'nullable|exists:mst_brands,id',
            'mst_client_id' => 'nullable|exists:mst_client,id',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|string',
            'image_url' => 'nullable|string',
            'is_active' => 'required|boolean',
            // Inventory fields (for initial stock setup)
            'initial_stock' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Create the product (no longer storing stock_quantity/min_stock_level)
            $product = Product::create([
                'name' => $validatedData['name'],
                'description' => $validatedData['description'] ?? null,
                'sku' => $validatedData['sku'] ?? null,
                'barcode' => $validatedData['barcode'] ?? null,
                'price' => $validatedData['price'],
                'cost_price' => $validatedData['cost_price'] ?? 0,
                'currency' => $validatedData['currency'] ?? 'IDR',
                'mst_product_category_id' => $validatedData['mst_product_category_id'] ?? null,
                'mst_brand_id' => $validatedData['mst_brand_id'] ?? null,
                'mst_client_id' => $validatedData['mst_client_id'] ?? null,
                'weight' => $validatedData['weight'] ?? null,
                'dimensions' => $validatedData['dimensions'] ?? null,
                'image_url' => $validatedData['image_url'] ?? null,
                'is_active' => $validatedData['is_active'],
            ]);

            // Create InventoryStock record (single source of truth for stock)
            $initialStock = $validatedData['initial_stock'] ?? 0;
            $reorderLevel = $validatedData['reorder_level'] ?? 0;
            $costPrice = $validatedData['cost_price'] ?? 0;

            InventoryStock::create([
                'product_id' => $product->id,
                'quantity_on_hand' => $initialStock,
                'quantity_reserved' => 0,
                'quantity_available' => $initialStock,
                'reorder_level' => $reorderLevel,
                'average_cost' => $costPrice,
                'last_cost' => $costPrice,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully.',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create product.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Update an existing product.
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sku' => 'nullable|string|max:100|unique:mst_products,sku,' . $product->id,
            'barcode' => 'nullable|string|max:100',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'currency' => 'required|string|max:3',
            'mst_product_category_id' => 'nullable|exists:mst_product_categories,id',
            'mst_brand_id' => 'nullable|exists:mst_brands,id',
            'mst_client_id' => 'nullable|exists:mst_client,id',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|string',
            'image_url' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            // Inventory fields
            'reorder_level' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Update product (without stock fields)
            $product->update([
                'name' => $validatedData['name'],
                'description' => $validatedData['description'] ?? null,
                'sku' => $validatedData['sku'] ?? null,
                'barcode' => $validatedData['barcode'] ?? null,
                'price' => $validatedData['price'],
                'cost_price' => $validatedData['cost_price'] ?? 0,
                'currency' => $validatedData['currency'],
                'mst_product_category_id' => $validatedData['mst_product_category_id'] ?? null,
                'mst_brand_id' => $validatedData['mst_brand_id'] ?? null,
                'mst_client_id' => $validatedData['mst_client_id'] ?? null,
                'weight' => $validatedData['weight'] ?? null,
                'dimensions' => $validatedData['dimensions'] ?? null,
                'image_url' => $validatedData['image_url'] ?? null,
                'is_active' => $validatedData['is_active'] ?? true,
            ]);

            // Update InventoryStock reorder_level if provided
            if (isset($validatedData['reorder_level'])) {
                $stock = InventoryStock::getOrCreate($product->id);
                $stock->reorder_level = $validatedData['reorder_level'];
                $stock->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update product.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Show the edit page for a product.
     */
    public function edit($id)
    {
        $product = Product::with('inventoryStock')->findOrFail($id);
        $stock = $product->inventoryStock;

        $data = [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'price' => $product->price,
            'cost_price' => $product->cost_price,
            'currency' => $product->currency,
            'mst_product_category_id' => $product->mst_product_category_id,
            'mst_brand_id' => $product->mst_brand_id,
            'mst_client_id' => $product->mst_client_id,
            'weight' => $product->weight,
            'dimensions' => $product->dimensions,
            'image_url' => $product->image_url,
            'is_active' => $product->is_active,
            // Inventory data (read-only display, editable reorder_level)
            'quantity_on_hand' => $stock->quantity_on_hand ?? 0,
            'quantity_available' => $stock->quantity_available ?? 0,
            'quantity_reserved' => $stock->quantity_reserved ?? 0,
            'reorder_level' => $stock->reorder_level ?? 0,
            'average_cost' => $stock->average_cost ?? 0,
            'stock_status' => $product->stock_status,
        ];

        $productCategories = ProductCategory::pluck('name', 'id');
        $brands = Brand::pluck('name', 'id');
        $customers = Customer::pluck('client_name', 'id');

        return Inertia::render('Product/Edit', [
            'product' => $data,
            'productCategories' => $productCategories,
            'brands' => $brands,
            'customers' => $customers,
        ]);
    }

    /**
     * Show product detail page.
     */
    public function show($id)
    {
        $product = Product::with([
            'category',
            'brand',
            'customer',
            'uom',
            'inventoryStock',
        ])->findOrFail($id);

        $stock = $product->inventoryStock;

        // Product data
        $productData = [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'price' => $product->price,
            'cost_price' => $product->cost_price,
            'currency' => $product->currency,
            'weight' => $product->weight,
            'dimensions' => $product->dimensions,
            'image_url' => $product->image_url,
            'is_active' => $product->is_active,
            'created_at' => $product->created_at?->format('d M Y H:i'),
            'updated_at' => $product->updated_at?->format('d M Y H:i'),
            // Relations
            'category' => $product->category?->name ?? 'N/A',
            'category_id' => $product->mst_product_category_id,
            'brand' => $product->brand?->name ?? 'N/A',
            'brand_id' => $product->mst_brand_id,
            'supplier' => $product->customer?->client_name ?? 'N/A',
            'supplier_id' => $product->mst_client_id,
            'uom' => $product->uom?->name ?? 'N/A',
            'uom_code' => $product->uom?->code ?? '-',
            // Inventory data
            'quantity_on_hand' => $stock->quantity_on_hand ?? 0,
            'quantity_available' => $stock->quantity_available ?? 0,
            'quantity_reserved' => $stock->quantity_reserved ?? 0,
            'reorder_level' => $stock->reorder_level ?? 0,
            'average_cost' => $stock->average_cost ?? $product->cost_price ?? 0,
            'last_cost' => $stock->last_cost ?? 0,
            'last_movement_at' => $stock->last_movement_at?->format('d M Y H:i'),
            'stock_status' => $product->stock_status,
            'is_low_stock' => $product->is_low_stock,
            // Profit margin
            'profit_margin' => $product->price > 0 && ($stock->average_cost ?? $product->cost_price ?? 0) > 0
                ? round((($product->price - ($stock->average_cost ?? $product->cost_price ?? 0)) / $product->price) * 100, 2)
                : 0,
        ];

        // Recent sales order items (last 10)
        $recentSales = $product->salesOrderItems()
            ->with(['salesOrder.customer'])
            ->whereHas('salesOrder')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'so_id' => $item->salesOrder->id,
                'so_number' => $item->salesOrder->so_number,
                'customer' => $item->salesOrder->customer?->client_name ?? 'N/A',
                'date' => $item->salesOrder->order_date?->format('d M Y'),
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'subtotal' => $item->subtotal,
                'status' => $item->salesOrder->status,
                'status_label' => ucfirst($item->salesOrder->status),
            ]);

        // Recent purchase order items (last 10)
        $recentPurchases = $product->purchaseOrderItems()
            ->with(['purchaseOrder.supplier'])
            ->whereHas('purchaseOrder')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'po_id' => $item->purchaseOrder->id,
                'po_number' => $item->purchaseOrder->po_number,
                'supplier' => $item->purchaseOrder->supplier?->client_name ?? 'N/A',
                'date' => $item->purchaseOrder->order_date?->format('d M Y'),
                'quantity' => $item->quantity,
                'unit_cost' => $item->unit_cost,
                'subtotal' => $item->subtotal,
                'status' => $item->purchaseOrder->status,
                'status_label' => ucfirst($item->purchaseOrder->status),
            ]);

        // Recent inventory movements (last 15)
        $recentMovements = $product->inventoryMovements()
            ->latest('movement_date')
            ->limit(15)
            ->get()
            ->map(fn($mov) => [
                'id' => $mov->id,
                'date' => $mov->movement_date?->format('d M Y H:i'),
                'type' => $mov->movement_type,
                'type_label' => str_replace('_', ' ', ucfirst($mov->movement_type)),
                'quantity' => $mov->quantity,
                'quantity_before' => $mov->quantity_before,
                'quantity_after' => $mov->quantity_after,
                'unit_cost' => $mov->unit_cost,
                'reference_type' => $mov->reference_type,
                'reference_id' => $mov->reference_id,
                'notes' => $mov->notes,
            ]);

        return Inertia::render('Product/Detail', [
            'product' => $productData,
            'recentSales' => $recentSales,
            'recentPurchases' => $recentPurchases,
            'recentMovements' => $recentMovements,
        ]);
    }
}

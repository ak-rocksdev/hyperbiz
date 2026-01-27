<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Currency;
use App\Models\Uom;
use App\Models\InventoryStock;
use App\Models\InventoryMovement;
use App\Models\CompanySetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SalesOrderController extends Controller
{
    /**
     * Display a list of sales orders.
     */
    public function list(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $paymentStatus = $request->input('payment_status');
        $perPage = $request->input('per_page', 10);

        $query = SalesOrder::with(['customer', 'items.product', 'createdBy'])
            ->orderByDesc('created_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('so_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('client_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($paymentStatus) {
            $query->where('payment_status', $paymentStatus);
        }

        $salesOrders = $query->paginate($perPage);

        $data = $salesOrders->getCollection()->map(function ($so) {
            return [
                'id' => $so->id,
                'so_number' => $so->so_number,
                'customer_name' => $so->customer->client_name ?? 'N/A',
                'order_date' => Carbon::parse($so->order_date)->format('d M Y'),
                'due_date' => $so->due_date ? Carbon::parse($so->due_date)->format('d M Y') : '-',
                'status' => strtolower($so->status ?? 'draft'),
                'status_label' => $so->status_label,
                'currency_code' => $so->currency_code,
                'grand_total' => $so->grand_total,
                'payment_status' => $so->payment_status,
                'payment_status_label' => $so->payment_status_label,
                'amount_paid' => $so->amount_paid,
                'amount_due' => $so->grand_total - $so->amount_paid,
                'items_count' => $so->items->count(),
                'created_by' => $so->createdBy->name ?? 'System',
                'created_at' => Carbon::parse($so->created_at)->format('d M Y H:i'),
            ];
        });

        // Calculate stats
        $nonCancelledOrders = SalesOrder::where('status', '!=', 'cancelled');

        $stats = [
            'total_orders' => SalesOrder::count(),
            'pending_orders' => SalesOrder::whereIn('status', ['confirmed', 'processing', 'shipped'])->count(),
            'total_value' => (clone $nonCancelledOrders)->sum('grand_total'),
            'unpaid_amount' => SalesOrder::where('status', '!=', 'cancelled')
                ->where('payment_status', '!=', 'paid')
                ->selectRaw('SUM(grand_total - amount_paid) as unpaid')
                ->value('unpaid') ?? 0,
        ];

        return Inertia::render('SalesOrder/List', [
            'salesOrders' => $data,
            'pagination' => [
                'total' => $salesOrders->total(),
                'per_page' => $salesOrders->perPage(),
                'current_page' => $salesOrders->currentPage(),
                'last_page' => $salesOrders->lastPage(),
            ],
            'stats' => $stats,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'payment_status' => $paymentStatus,
            ],
            'statuses' => [
                ['value' => 'draft', 'label' => 'Draft'],
                ['value' => 'confirmed', 'label' => 'Confirmed'],
                ['value' => 'processing', 'label' => 'Processing'],
                ['value' => 'shipped', 'label' => 'Shipped'],
                ['value' => 'delivered', 'label' => 'Delivered'],
                ['value' => 'cancelled', 'label' => 'Cancelled'],
            ],
        ]);
    }

    /**
     * Show the create form.
     */
    public function create()
    {
        // Get customers (clients that can be used for selling)
        $customers = Customer::whereHas('customerType', function ($q) {
            $q->where('can_sell', true);
        })->with('customerType', 'address')->get()->map(function ($customer) {
            return [
                'id' => $customer->id,
                'client_name' => $customer->client_name,
                'type' => $customer->customerType->client_type ?? 'N/A',
                'phone' => $customer->client_phone_number,
                'email' => $customer->email,
                'address' => $customer->address ? $customer->address->address : null,
            ];
        });

        // Get active products with stock info
        $products = Product::where('is_active', true)
            ->with(['uom', 'category', 'brand', 'inventoryStock'])
            ->get()
            ->map(function ($product) {
                $stock = $product->inventoryStock;
                return [
                    'id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'selling_price' => $product->price,
                    'cost_price' => $product->cost_price ?? 0,
                    'stock_quantity' => $product->stock_quantity,
                    'available_stock' => $stock ? $stock->quantity_available : $product->stock_quantity,
                    'uom' => $product->uom ? [
                        'id' => $product->uom->id,
                        'code' => $product->uom->code,
                        'name' => $product->uom->name,
                    ] : null,
                    'category' => $product->category->name ?? null,
                    'brand' => $product->brand->name ?? null,
                ];
            });

        $currencies = Currency::where('is_active', true)->get();
        $uoms = Uom::where('is_active', true)->get();

        // Get company settings for defaults
        $company = Auth::user()->current_team_id ?? 1;
        $defaultCurrency = CompanySetting::getValue($company, 'default_currency', 'IDR');
        $defaultTaxEnabled = CompanySetting::getValue($company, 'default_tax_enabled', 'false') === 'true';
        $defaultTaxName = CompanySetting::getValue($company, 'default_tax_name', 'PPN');
        $defaultTaxPercentage = (float) CompanySetting::getValue($company, 'default_tax_percentage', '11');

        return Inertia::render('SalesOrder/Create', [
            'customers' => $customers,
            'products' => $products,
            'currencies' => $currencies,
            'uoms' => $uoms,
            'defaultCurrency' => $defaultCurrency,
            'defaultTaxEnabled' => $defaultTaxEnabled,
            'defaultTaxName' => $defaultTaxName,
            'defaultTaxPercentage' => $defaultTaxPercentage,
            'statuses' => [
                ['value' => 'draft', 'label' => 'Draft'],
                ['value' => 'confirmed', 'label' => 'Confirmed'],
            ],
        ]);
    }

    /**
     * Store a new sales order.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:mst_client,id',
            'order_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:order_date',
            'currency_code' => 'required|string|size:3',
            'exchange_rate' => 'required|numeric|min:0.000001',
            'discount_type' => 'nullable|in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'tax_enabled' => 'boolean',
            'tax_name' => 'nullable|string|max:50',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'shipping_fee' => 'nullable|numeric|min:0',
            'shipping_address' => 'nullable|string|max:500',
            'payment_terms' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:mst_products,id',
            'items.*.uom_id' => 'nullable|exists:mst_uom,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.notes' => 'nullable|string|max:255',
        ], [
            'items.required' => 'At least one item is required.',
            'items.*.product_id.required' => 'Product is required for all items.',
            'items.*.quantity.required' => 'Quantity is required for all items.',
            'items.*.unit_price.required' => 'Unit price is required for all items.',
        ]);

        DB::beginTransaction();

        try {
            // Determine status based on save_as_draft parameter
            $saveAsDraft = $request->boolean('save_as_draft', true);
            $status = $saveAsDraft ? SalesOrder::STATUS_DRAFT : SalesOrder::STATUS_CONFIRMED;

            // Create Sales Order
            $so = SalesOrder::create([
                'customer_id' => $validated['customer_id'],
                'order_date' => $validated['order_date'],
                'due_date' => $validated['due_date'] ?? null,
                'status' => $status,
                'currency_code' => $validated['currency_code'],
                'exchange_rate' => $validated['exchange_rate'],
                'discount_type' => $validated['discount_type'] ?? null,
                'discount_value' => $validated['discount_value'] ?? 0,
                'tax_enabled' => $validated['tax_enabled'] ?? false,
                'tax_name' => $validated['tax_name'] ?? null,
                'tax_percentage' => $validated['tax_percentage'] ?? 0,
                'shipping_fee' => $validated['shipping_fee'] ?? 0,
                'shipping_address' => $validated['shipping_address'] ?? null,
                'payment_terms' => $validated['payment_terms'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create items
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $gross = $item['quantity'] * $item['unit_price'];
                $discount = $gross * (($item['discount_percentage'] ?? 0) / 100);
                $itemSubtotal = $gross - $discount;
                $subtotal += $itemSubtotal;

                SalesOrderItem::create([
                    'sales_order_id' => $so->id,
                    'product_id' => $item['product_id'],
                    'uom_id' => $item['uom_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'quantity_shipped' => 0,
                    'unit_price' => $item['unit_price'],
                    'discount_percentage' => $item['discount_percentage'] ?? 0,
                    'subtotal' => $itemSubtotal,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            $so->subtotal = $subtotal;
            $so->calculateTotals();

            DB::commit();

            $statusMessage = $saveAsDraft ? 'saved as draft' : 'created and confirmed';
            return response()->json([
                'success' => true,
                'message' => "Sales Order {$statusMessage} successfully.",
                'data' => [
                    'id' => $so->id,
                    'so_number' => $so->so_number,
                    'status' => $status,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create Sales Order.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Show sales order details.
     */
    public function show($id)
    {
        $so = SalesOrder::with([
            'customer.customerType',
            'customer.address',
            'items.product',
            'items.uom',
            'shipments.items',
            'payments',
            'createdBy',
            'updatedBy',
        ])->findOrFail($id);

        $data = [
            'id' => $so->id,
            'so_number' => $so->so_number,
            'customer' => [
                'id' => $so->customer->id,
                'name' => $so->customer->client_name,
                'type' => $so->customer->customerType->client_type ?? 'N/A',
                'phone' => $so->customer->client_phone_number,
                'email' => $so->customer->email,
                'address' => $so->customer->address ? $so->customer->address->address : null,
            ],
            'customer_name' => $so->customer->client_name ?? 'N/A',
            'order_date' => Carbon::parse($so->order_date)->format('d M Y'),
            'due_date' => $so->due_date ? Carbon::parse($so->due_date)->format('d M Y') : null,
            'status' => strtolower($so->status ?? 'draft'),
            'status_label' => ucfirst(strtolower($so->status ?? 'draft')),
            'currency_code' => $so->currency_code,
            'exchange_rate' => $so->exchange_rate,
            'subtotal' => $so->subtotal,
            'discount_type' => $so->discount_type,
            'discount_value' => $so->discount_value,
            'discount_amount' => $so->discount_amount,
            'tax_enabled' => $so->tax_enabled,
            'tax_name' => $so->tax_name,
            'tax_percentage' => $so->tax_percentage,
            'tax_amount' => $so->tax_amount,
            'shipping_fee' => $so->shipping_fee,
            'shipping_address' => $so->shipping_address,
            'grand_total' => $so->grand_total,
            'payment_terms' => $so->payment_terms,
            'payment_status' => strtolower($so->payment_status ?? 'unpaid'),
            'payment_status_label' => ucfirst(strtolower($so->payment_status ?? 'unpaid')),
            'amount_paid' => $so->amount_paid,
            'amount_due' => $so->grand_total - $so->amount_paid,
            'gross_profit' => $so->gross_profit,
            'notes' => $so->notes,
            'items' => $so->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name ?? 'N/A',
                    'sku' => $item->product->sku ?? null,
                    'uom' => $item->uom ? [
                        'id' => $item->uom->id,
                        'code' => $item->uom->code,
                        'name' => $item->uom->name,
                    ] : null,
                    'uom_code' => $item->uom->code ?? null,
                    'quantity' => $item->quantity,
                    'quantity_shipped' => $item->quantity_shipped,
                    'remaining_quantity' => $item->quantity - $item->quantity_shipped,
                    'unit_price' => $item->unit_price,
                    'unit_cost' => $item->unit_cost,
                    'discount_percentage' => $item->discount_percentage,
                    'discount' => ($item->quantity * $item->unit_price) * ($item->discount_percentage / 100),
                    'subtotal' => $item->subtotal,
                    'line_profit' => $item->line_profit,
                    'notes' => $item->notes,
                ];
            }),
            'shipments' => $so->shipments->map(function ($shp) {
                return [
                    'id' => $shp->id,
                    'shipment_number' => $shp->shipment_number,
                    'shipment_date' => Carbon::parse($shp->shipment_date)->format('d M Y'),
                    'courier' => $shp->courier,
                    'tracking_number' => $shp->tracking_number,
                    'status' => $shp->status,
                ];
            }),
            'payments' => $so->payments->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'payment_date' => Carbon::parse($payment->payment_date)->format('d M Y'),
                    'amount' => $payment->amount,
                    'payment_method' => $payment->payment_method_label,
                    'status' => $payment->status,
                ];
            }),
            'created_by' => $so->createdBy->name ?? 'System',
            'created_at' => Carbon::parse($so->created_at)->format('d M Y H:i'),
            'updated_by' => $so->updatedBy->name ?? null,
            'updated_at' => $so->updated_at ? Carbon::parse($so->updated_at)->format('d M Y H:i') : null,
        ];

        return Inertia::render('SalesOrder/Detail', [
            'salesOrder' => $data,
        ]);
    }

    /**
     * Get sales order details via API.
     */
    public function detailApi($id)
    {
        $so = SalesOrder::with([
            'customer',
            'items.product',
            'items.uom',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $so,
        ]);
    }

    /**
     * Show edit form.
     */
    public function edit($id)
    {
        $so = SalesOrder::with(['customer', 'items.product', 'items.uom'])->findOrFail($id);

        if ($so->status !== SalesOrder::STATUS_DRAFT) {
            return redirect()->route('sales-orders.show', $id)
                ->with('error', 'Only draft orders can be edited.');
        }

        $customers = Customer::whereHas('customerType', function ($q) {
            $q->where('can_sell', true);
        })->with('customerType')->get()->map(function ($customer) {
            return [
                'id' => $customer->id,
                'client_name' => $customer->client_name,
                'type' => $customer->customerType->client_type ?? 'N/A',
            ];
        });

        $products = Product::where('is_active', true)
            ->with(['uom', 'inventoryStock'])
            ->get()
            ->map(function ($product) {
                $stock = $product->inventoryStock;
                return [
                    'id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'selling_price' => $product->price,
                    'available_stock' => $stock ? $stock->quantity_available : $product->stock_quantity,
                    'uom' => $product->uom,
                ];
            });

        $currencies = Currency::where('is_active', true)->get();
        $uoms = Uom::where('is_active', true)->get();

        // Format SO data with flattened items
        $soData = $so->toArray();
        $soData['items'] = $so->items->map(function ($item) {
            $stock = $item->product->inventoryStock ?? null;
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name ?? 'N/A',
                'sku' => $item->product->sku ?? null,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount_percentage' => (float) ($item->discount_percentage ?? 0),
                'available_stock' => $stock ? (float) $stock->quantity_available : 0,
                'notes' => $item->notes,
            ];
        });

        return Inertia::render('SalesOrder/Edit', [
            'salesOrder' => $soData,
            'customers' => $customers,
            'products' => $products,
            'currencies' => $currencies,
            'uoms' => $uoms,
        ]);
    }

    /**
     * Update sales order.
     */
    public function update(Request $request, $id)
    {
        $so = SalesOrder::findOrFail($id);

        if ($so->status !== SalesOrder::STATUS_DRAFT) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft orders can be updated.',
            ], 422);
        }

        $validated = $request->validate([
            'customer_id' => 'required|exists:mst_client,id',
            'order_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:order_date',
            'currency_code' => 'required|string|size:3',
            'exchange_rate' => 'required|numeric|min:0.000001',
            'discount_type' => 'nullable|in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'tax_enabled' => 'boolean',
            'tax_name' => 'nullable|string|max:50',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'shipping_fee' => 'nullable|numeric|min:0',
            'shipping_address' => 'nullable|string|max:500',
            'payment_terms' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:mst_products,id',
            'items.*.uom_id' => 'nullable|exists:mst_uom,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.notes' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $so->update([
                'customer_id' => $validated['customer_id'],
                'order_date' => $validated['order_date'],
                'due_date' => $validated['due_date'] ?? null,
                'currency_code' => $validated['currency_code'],
                'exchange_rate' => $validated['exchange_rate'],
                'discount_type' => $validated['discount_type'] ?? null,
                'discount_value' => $validated['discount_value'] ?? 0,
                'tax_enabled' => $validated['tax_enabled'] ?? false,
                'tax_name' => $validated['tax_name'] ?? null,
                'tax_percentage' => $validated['tax_percentage'] ?? 0,
                'shipping_fee' => $validated['shipping_fee'] ?? 0,
                'shipping_address' => $validated['shipping_address'] ?? null,
                'payment_terms' => $validated['payment_terms'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $so->items()->delete();

            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $gross = $item['quantity'] * $item['unit_price'];
                $discount = $gross * (($item['discount_percentage'] ?? 0) / 100);
                $itemSubtotal = $gross - $discount;
                $subtotal += $itemSubtotal;

                SalesOrderItem::create([
                    'sales_order_id' => $so->id,
                    'product_id' => $item['product_id'],
                    'uom_id' => $item['uom_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'quantity_shipped' => 0,
                    'unit_price' => $item['unit_price'],
                    'discount_percentage' => $item['discount_percentage'] ?? 0,
                    'subtotal' => $itemSubtotal,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            $so->subtotal = $subtotal;
            $so->calculateTotals();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sales Order updated successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update Sales Order.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Confirm sales order (reserve stock).
     */
    public function confirm($id)
    {
        $so = SalesOrder::with('items.product')->findOrFail($id);

        if ($so->status !== SalesOrder::STATUS_DRAFT) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft orders can be confirmed.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Check and reserve stock for each item
            foreach ($so->items as $item) {
                $stock = InventoryStock::getOrCreate($item->product_id);

                if (!$stock->hasAvailable($item->quantity)) {
                    $available = rtrim(rtrim(number_format($stock->quantity_available, 3, '.', ''), '0'), '.');
                    $required = rtrim(rtrim(number_format($item->quantity, 3, '.', ''), '0'), '.');
                    throw new \Exception("Insufficient stock for product: {$item->product->name}. Available: {$available}, Required: {$required}");
                }

                $stock->reserveStock($item->quantity);
            }

            $so->status = SalesOrder::STATUS_CONFIRMED;
            $so->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sales Order confirmed and stock reserved.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel sales order (release reserved stock).
     */
    public function cancel($id)
    {
        $so = SalesOrder::with('items')->findOrFail($id);

        if (!in_array($so->status, [SalesOrder::STATUS_DRAFT, SalesOrder::STATUS_CONFIRMED])) {
            return response()->json([
                'success' => false,
                'message' => 'This order cannot be cancelled.',
            ], 422);
        }

        // Check if any items have been shipped
        $hasShipped = $so->items->sum('quantity_shipped') > 0;
        if ($hasShipped) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel order with shipped items.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Release reserved stock if order was confirmed
            if ($so->status === SalesOrder::STATUS_CONFIRMED) {
                foreach ($so->items as $item) {
                    $stock = InventoryStock::where('product_id', $item->product_id)->first();
                    if ($stock) {
                        $stock->releaseReserved($item->quantity);
                    }
                }
            }

            $so->status = SalesOrder::STATUS_CANCELLED;
            $so->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sales Order cancelled successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel order.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Delete sales order.
     */
    public function delete($id)
    {
        $so = SalesOrder::findOrFail($id);

        if ($so->status !== SalesOrder::STATUS_DRAFT) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft orders can be deleted.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            $so->items()->delete();
            $so->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sales Order deleted successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Sales Order.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Mark order as shipped/delivered (deduct stock, calculate COGS).
     */
    public function markAsDelivered($id)
    {
        $so = SalesOrder::with('items.product')->findOrFail($id);

        if (!in_array($so->status, [SalesOrder::STATUS_CONFIRMED, SalesOrder::STATUS_PROCESSING, SalesOrder::STATUS_SHIPPED])) {
            return response()->json([
                'success' => false,
                'message' => 'Order must be confirmed before marking as delivered.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            foreach ($so->items as $item) {
                $stock = InventoryStock::getOrCreate($item->product_id);

                // Release reservation
                $stock->releaseReserved($item->quantity);

                // Record movement (deduct stock)
                InventoryMovement::record(
                    $item->product_id,
                    InventoryMovement::TYPE_SALES_OUT,
                    $item->quantity,
                    'sales_order',
                    $so->id,
                    $stock->average_cost,
                    "Sold via SO: {$so->so_number}"
                );

                // Store COGS in item
                $item->unit_cost = $stock->average_cost;
                $item->quantity_shipped = $item->quantity;
                $item->save();
            }

            $so->status = SalesOrder::STATUS_DELIVERED;
            $so->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order marked as delivered. Stock has been deducted.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to process delivery.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Mark order as shipped (simple status change, no stock deduction).
     */
    public function markAsShipped($id)
    {
        $so = SalesOrder::findOrFail($id);

        if (!in_array($so->status, [SalesOrder::STATUS_CONFIRMED, SalesOrder::STATUS_PROCESSING])) {
            return response()->json([
                'success' => false,
                'message' => 'Order must be confirmed before marking as shipped.',
            ], 422);
        }

        $so->status = SalesOrder::STATUS_SHIPPED;
        $so->save();

        return response()->json([
            'success' => true,
            'message' => 'Order marked as shipped.',
        ]);
    }
}

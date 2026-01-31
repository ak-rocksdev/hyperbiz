<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReceiving;
use App\Models\PurchaseReceivingItem;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\Product;
use App\Models\Currency;
use App\Models\Uom;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Models\CompanySetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;

class PurchaseOrderController extends Controller
{
    /**
     * Display a list of purchase orders.
     */
    public function list(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $paymentStatus = $request->input('payment_status');
        $perPage = $request->input('per_page', 10);

        $query = PurchaseOrder::with(['supplier', 'items.product', 'createdBy'])
            ->orderByDesc('created_at');

        // Search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($sq) use ($search) {
                        $sq->where('client_name', 'like', "%{$search}%");
                    });
            });
        }

        // Status filter
        if ($status) {
            $query->where('status', $status);
        }

        // Payment status filter
        if ($paymentStatus) {
            $query->where('payment_status', $paymentStatus);
        }

        $purchaseOrders = $query->paginate($perPage);

        $data = $purchaseOrders->getCollection()->map(function ($po) {
            return [
                'id' => $po->id,
                'po_number' => $po->po_number,
                'supplier_name' => $po->supplier->client_name ?? 'N/A',
                'order_date' => Carbon::parse($po->order_date)->format('d M Y'),
                'expected_date' => $po->expected_date ? Carbon::parse($po->expected_date)->format('d M Y') : '-',
                'status' => strtolower($po->status ?? 'draft'),
                'status_label' => $po->status_label,
                'currency_code' => $po->currency_code,
                'grand_total' => $po->grand_total,
                'payment_status' => $po->payment_status,
                'payment_status_label' => $po->payment_status_label,
                'amount_paid' => $po->amount_paid,
                'amount_due' => $po->grand_total - $po->amount_paid,
                'items_count' => $po->items->count(),
                'created_by' => $po->createdBy->name ?? 'System',
                'created_at' => Carbon::parse($po->created_at)->format('d M Y H:i'),
            ];
        });

        // Statistics
        $nonCancelledOrders = PurchaseOrder::where('status', '!=', 'cancelled');

        $stats = [
            'total_orders' => PurchaseOrder::count(),
            'pending_orders' => PurchaseOrder::whereIn('status', ['confirmed', 'partial'])->count(),
            'total_value' => (clone $nonCancelledOrders)->sum('grand_total'),
            'unpaid_amount' => PurchaseOrder::where('status', '!=', 'cancelled')
                ->where('payment_status', '!=', 'paid')
                ->selectRaw('SUM(grand_total - amount_paid) as unpaid')
                ->value('unpaid') ?? 0,
        ];

        return Inertia::render('PurchaseOrder/List', [
            'purchaseOrders' => $data,
            'pagination' => [
                'total' => $purchaseOrders->total(),
                'per_page' => $purchaseOrders->perPage(),
                'current_page' => $purchaseOrders->currentPage(),
                'last_page' => $purchaseOrders->lastPage(),
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
                ['value' => 'partial', 'label' => 'Partial'],
                ['value' => 'received', 'label' => 'Received'],
                ['value' => 'cancelled', 'label' => 'Cancelled'],
            ],
        ]);
    }

    /**
     * Show the create form.
     */
    public function create()
    {
        // Get suppliers (clients that can be used for purchasing)
        $suppliers = Customer::whereHas('customerType', function ($q) {
            $q->where('can_purchase', true);
        })->with('customerType')->get()->map(function ($supplier) {
            return [
                'id' => $supplier->id,
                'client_name' => $supplier->client_name,
                'type' => $supplier->customerType->client_type ?? 'N/A',
                'phone' => $supplier->client_phone_number,
                'email' => $supplier->email,
            ];
        });

        // Get active products
        $products = Product::where('is_active', true)
            ->with(['uom', 'category', 'brand'])
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'cost_price' => $product->cost_price ?? 0,
                    'price' => $product->price,
                    'stock_quantity' => $product->stock_quantity,
                    'uom' => $product->uom ? [
                        'id' => $product->uom->id,
                        'code' => $product->uom->code,
                        'name' => $product->uom->name,
                    ] : null,
                    'category' => $product->category->name ?? null,
                    'brand' => $product->brand->name ?? null,
                ];
            });

        // Get currencies
        $currencies = Currency::where('is_active', true)->get();

        // Get UOMs
        $uoms = Uom::where('is_active', true)->get();

        // Get company settings for defaults
        $company = Auth::user()->current_team_id ?? 1;
        $defaultCurrency = CompanySetting::getValue($company, 'default_currency', 'IDR');
        $defaultTaxEnabled = CompanySetting::getValue($company, 'default_tax_enabled', 'false') === 'true';
        $defaultTaxName = CompanySetting::getValue($company, 'default_tax_name', 'PPN');
        $defaultTaxPercentage = (float) CompanySetting::getValue($company, 'default_tax_percentage', '11');

        return Inertia::render('PurchaseOrder/Create', [
            'suppliers' => $suppliers,
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
     * Store a new purchase order.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:mst_client,id',
            'order_date' => 'required|date',
            'expected_date' => 'nullable|date|after_or_equal:order_date',
            'currency_code' => 'required|string|size:3',
            'exchange_rate' => 'required|numeric|min:0.000001',
            'discount_type' => 'nullable|in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'tax_enabled' => 'boolean',
            'tax_name' => 'nullable|string|max:50',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'payment_terms' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:mst_products,id',
            'items.*.uom_id' => 'nullable|exists:mst_uom,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.notes' => 'nullable|string|max:255',
        ], [
            'items.required' => 'At least one item is required.',
            'items.*.product_id.required' => 'Product is required for all items.',
            'items.*.quantity.required' => 'Quantity is required for all items.',
            'items.*.unit_cost.required' => 'Unit cost is required for all items.',
        ]);

        DB::beginTransaction();

        try {
            // Determine status based on save_as_draft parameter
            $saveAsDraft = $request->boolean('save_as_draft', true);
            $status = $saveAsDraft ? PurchaseOrder::STATUS_DRAFT : PurchaseOrder::STATUS_CONFIRMED;

            // Create Purchase Order
            $po = PurchaseOrder::create([
                'supplier_id' => $validated['supplier_id'],
                'order_date' => $validated['order_date'],
                'expected_date' => $validated['expected_date'] ?? null,
                'status' => $status,
                'currency_code' => $validated['currency_code'],
                'exchange_rate' => $validated['exchange_rate'],
                'discount_type' => $validated['discount_type'] ?? null,
                'discount_value' => $validated['discount_value'] ?? 0,
                'shipping_cost' => $validated['shipping_cost'] ?? 0,
                'tax_enabled' => $validated['tax_enabled'] ?? false,
                'tax_name' => $validated['tax_name'] ?? null,
                'tax_percentage' => $validated['tax_percentage'] ?? 0,
                'payment_terms' => $validated['payment_terms'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create items
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $gross = $item['quantity'] * $item['unit_cost'];
                $discount = $gross * (($item['discount_percentage'] ?? 0) / 100);
                $itemSubtotal = $gross - $discount;
                $subtotal += $itemSubtotal;

                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $item['product_id'],
                    'uom_id' => $item['uom_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'quantity_received' => 0,
                    'unit_cost' => $item['unit_cost'],
                    'discount_percentage' => $item['discount_percentage'] ?? 0,
                    'subtotal' => $itemSubtotal,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            // Calculate totals
            $po->subtotal = $subtotal;
            $po->calculateTotals();

            DB::commit();

            $statusMessage = $saveAsDraft ? 'saved as draft' : 'created and confirmed';
            return response()->json([
                'success' => true,
                'message' => "Purchase Order {$statusMessage} successfully.",
                'data' => [
                    'id' => $po->id,
                    'po_number' => $po->po_number,
                    'status' => $status,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create Purchase Order.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Show purchase order details.
     */
    public function show($id)
    {
        $po = PurchaseOrder::with([
            'supplier.customerType',
            'items.product',
            'items.uom',
            'receivings.items',
            'payments',
            'createdBy',
            'updatedBy',
        ])->findOrFail($id);

        $data = [
            'id' => $po->id,
            'po_number' => $po->po_number,
            'supplier' => [
                'id' => $po->supplier->id,
                'name' => $po->supplier->client_name,
                'type' => $po->supplier->customerType->client_type ?? 'N/A',
                'phone' => $po->supplier->client_phone_number,
                'email' => $po->supplier->email,
            ],
            'supplier_name' => $po->supplier->client_name ?? 'N/A',
            'order_date' => Carbon::parse($po->order_date)->format('d M Y'),
            'expected_date' => $po->expected_date ? Carbon::parse($po->expected_date)->format('d M Y') : null,
            'status' => strtolower($po->status ?? 'draft'),
            'status_label' => ucfirst(strtolower($po->status ?? 'draft')),
            'currency_code' => $po->currency_code,
            'exchange_rate' => $po->exchange_rate,
            'subtotal' => $po->subtotal,
            'discount_type' => $po->discount_type,
            'discount_value' => $po->discount_value,
            'discount_amount' => $po->discount_amount,
            'tax_enabled' => $po->tax_enabled,
            'tax_name' => $po->tax_name,
            'tax_percentage' => $po->tax_percentage,
            'tax_amount' => $po->tax_amount,
            'grand_total' => $po->grand_total,
            'payment_terms' => $po->payment_terms,
            'payment_status' => strtolower($po->payment_status ?? 'unpaid'),
            'payment_status_label' => ucfirst(strtolower($po->payment_status ?? 'unpaid')),
            'amount_paid' => $po->amount_paid,
            'amount_due' => $po->grand_total - $po->amount_paid,
            'notes' => $po->notes,
            'items' => $po->items->map(function ($item) {
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
                    'quantity_received' => $item->quantity_received,
                    'quantity_remaining' => $item->quantity - $item->quantity_received,
                    'unit_cost' => $item->unit_cost,
                    'discount_percentage' => $item->discount_percentage,
                    'discount' => ($item->quantity * $item->unit_cost) * ($item->discount_percentage / 100),
                    'subtotal' => $item->subtotal,
                    'notes' => $item->notes,
                ];
            }),
            'receivings' => $po->receivings->map(function ($rcv) {
                return [
                    'id' => $rcv->id,
                    'receiving_number' => $rcv->receiving_number,
                    'receiving_date' => Carbon::parse($rcv->receiving_date)->format('d M Y'),
                    'status' => $rcv->status,
                    'items_count' => $rcv->items->count(),
                ];
            }),
            'payments' => $po->payments->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'payment_date' => Carbon::parse($payment->payment_date)->format('d M Y'),
                    'amount' => $payment->amount,
                    'payment_method' => $payment->payment_method_label,
                    'status' => $payment->status,
                ];
            }),
            'created_by' => $po->createdBy->name ?? 'System',
            'created_at' => Carbon::parse($po->created_at)->format('d M Y H:i'),
            'updated_by' => $po->updatedBy->name ?? null,
            'updated_at' => $po->updated_at ? Carbon::parse($po->updated_at)->format('d M Y H:i') : null,
        ];

        return Inertia::render('PurchaseOrder/Detail', [
            'purchaseOrder' => $data,
        ]);
    }

    /**
     * Get purchase order details via API.
     */
    public function detailApi($id)
    {
        $po = PurchaseOrder::with([
            'supplier',
            'items.product',
            'items.uom',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $po,
        ]);
    }

    /**
     * Show edit form.
     */
    public function edit($id)
    {
        $po = PurchaseOrder::with(['supplier', 'items.product', 'items.uom'])->findOrFail($id);

        // Only draft orders can be edited
        if ($po->status !== PurchaseOrder::STATUS_DRAFT) {
            return redirect()->route('purchase-orders.show', $id)
                ->with('error', 'Only draft orders can be edited.');
        }

        // Get suppliers
        $suppliers = Customer::whereHas('customerType', function ($q) {
            $q->where('can_purchase', true);
        })->with('customerType')->get()->map(function ($supplier) {
            return [
                'id' => $supplier->id,
                'client_name' => $supplier->client_name,
                'type' => $supplier->customerType->client_type ?? 'N/A',
            ];
        });

        // Get products
        $products = Product::where('is_active', true)
            ->with('uom')
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'cost_price' => $product->cost_price ?? 0,
                    'uom' => $product->uom,
                ];
            });

        $currencies = Currency::where('is_active', true)->get();
        $uoms = Uom::where('is_active', true)->get();

        // Format PO data with flattened items
        $poData = $po->toArray();
        $poData['items'] = $po->items->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name ?? 'N/A',
                'sku' => $item->product->sku ?? null,
                'quantity' => (float) $item->quantity,
                'unit_cost' => (float) $item->unit_cost,
                'discount_percentage' => (float) ($item->discount_percentage ?? 0),
                'notes' => $item->notes,
            ];
        });

        return Inertia::render('PurchaseOrder/Edit', [
            'purchaseOrder' => $poData,
            'suppliers' => $suppliers,
            'products' => $products,
            'currencies' => $currencies,
            'uoms' => $uoms,
        ]);
    }

    /**
     * Update purchase order.
     */
    public function update(Request $request, $id)
    {
        $po = PurchaseOrder::findOrFail($id);

        // Only draft orders can be updated
        if ($po->status !== PurchaseOrder::STATUS_DRAFT) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft orders can be updated.',
            ], 422);
        }

        $validated = $request->validate([
            'supplier_id' => 'required|exists:mst_client,id',
            'order_date' => 'required|date',
            'expected_date' => 'nullable|date|after_or_equal:order_date',
            'currency_code' => 'required|string|size:3',
            'exchange_rate' => 'required|numeric|min:0.000001',
            'discount_type' => 'nullable|in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'tax_enabled' => 'boolean',
            'tax_name' => 'nullable|string|max:50',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'payment_terms' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:mst_products,id',
            'items.*.uom_id' => 'nullable|exists:mst_uom,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.notes' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            // Update PO
            $po->update([
                'supplier_id' => $validated['supplier_id'],
                'order_date' => $validated['order_date'],
                'expected_date' => $validated['expected_date'] ?? null,
                'currency_code' => $validated['currency_code'],
                'exchange_rate' => $validated['exchange_rate'],
                'discount_type' => $validated['discount_type'] ?? null,
                'discount_value' => $validated['discount_value'] ?? 0,
                'tax_enabled' => $validated['tax_enabled'] ?? false,
                'tax_name' => $validated['tax_name'] ?? null,
                'tax_percentage' => $validated['tax_percentage'] ?? 0,
                'payment_terms' => $validated['payment_terms'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Delete existing items and recreate
            $po->items()->delete();

            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $gross = $item['quantity'] * $item['unit_cost'];
                $discount = $gross * (($item['discount_percentage'] ?? 0) / 100);
                $itemSubtotal = $gross - $discount;
                $subtotal += $itemSubtotal;

                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $item['product_id'],
                    'uom_id' => $item['uom_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'quantity_received' => 0,
                    'unit_cost' => $item['unit_cost'],
                    'discount_percentage' => $item['discount_percentage'] ?? 0,
                    'subtotal' => $itemSubtotal,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            $po->subtotal = $subtotal;
            $po->calculateTotals();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase Order updated successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update Purchase Order.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Confirm purchase order.
     */
    public function confirm($id)
    {
        $po = PurchaseOrder::findOrFail($id);

        if ($po->status !== PurchaseOrder::STATUS_DRAFT) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft orders can be confirmed.',
            ], 422);
        }

        $po->status = PurchaseOrder::STATUS_CONFIRMED;
        $po->save();

        return response()->json([
            'success' => true,
            'message' => 'Purchase Order confirmed successfully.',
        ]);
    }

    /**
     * Cancel purchase order.
     */
    public function cancel($id)
    {
        $po = PurchaseOrder::with('items')->findOrFail($id);

        // Can only cancel draft or confirmed orders without any receiving
        if (!in_array($po->status, [PurchaseOrder::STATUS_DRAFT, PurchaseOrder::STATUS_CONFIRMED])) {
            return response()->json([
                'success' => false,
                'message' => 'This order cannot be cancelled.',
            ], 422);
        }

        // Check if any items have been received
        $hasReceived = $po->items->sum('quantity_received') > 0;
        if ($hasReceived) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel order with received items.',
            ], 422);
        }

        $po->status = PurchaseOrder::STATUS_CANCELLED;
        $po->save();

        return response()->json([
            'success' => true,
            'message' => 'Purchase Order cancelled successfully.',
        ]);
    }

    /**
     * Delete purchase order.
     */
    public function delete($id)
    {
        $po = PurchaseOrder::with('items', 'receivings')->findOrFail($id);

        // Can only delete draft orders
        if ($po->status !== PurchaseOrder::STATUS_DRAFT) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft orders can be deleted.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            $po->items()->delete();
            $po->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase Order deleted successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Purchase Order.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Receive items (create receiving record).
     */
    public function receive(Request $request, $id)
    {
        $po = PurchaseOrder::with('items')->findOrFail($id);

        // Validate PO status
        if (!in_array($po->status, [PurchaseOrder::STATUS_CONFIRMED, PurchaseOrder::STATUS_PARTIAL])) {
            return response()->json([
                'success' => false,
                'message' => 'Only confirmed or partial orders can receive items.',
            ], 422);
        }

        $validated = $request->validate([
            'receiving_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.purchase_order_item_id' => 'required|exists:purchase_order_items,id',
            'items.*.quantity_received' => 'required|numeric|min:0.001',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            // Validate quantities
            foreach ($validated['items'] as $item) {
                $poItem = $po->items->find($item['purchase_order_item_id']);
                if (!$poItem) {
                    throw new \Exception("Invalid PO item: {$item['purchase_order_item_id']}");
                }

                $remainingQty = $poItem->quantity - $poItem->quantity_received;
                if ($item['quantity_received'] > $remainingQty) {
                    throw new \Exception("Received quantity exceeds remaining for product: {$poItem->product->name}");
                }
            }

            // Create receiving record
            $receiving = PurchaseReceiving::create([
                'purchase_order_id' => $po->id,
                'receiving_date' => $validated['receiving_date'],
                'status' => PurchaseReceiving::STATUS_DRAFT,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create receiving items
            foreach ($validated['items'] as $item) {
                $poItem = $po->items->find($item['purchase_order_item_id']);

                PurchaseReceivingItem::create([
                    'purchase_receiving_id' => $receiving->id,
                    'purchase_order_item_id' => $item['purchase_order_item_id'],
                    'product_id' => $poItem->product_id,
                    'quantity_received' => $item['quantity_received'],
                    'unit_cost' => $item['unit_cost'] ?? $poItem->unit_cost,
                ]);
            }

            // Auto-confirm receiving
            $receiving->confirm();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Items received successfully.',
                'data' => [
                    'receiving_number' => $receiving->receiving_number,
                ],
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
     * Quick receive all remaining items.
     */
    public function receiveAll($id)
    {
        $po = PurchaseOrder::with('items')->findOrFail($id);

        if (!in_array($po->status, [PurchaseOrder::STATUS_CONFIRMED, PurchaseOrder::STATUS_PARTIAL])) {
            return response()->json([
                'success' => false,
                'message' => 'Only confirmed or partial orders can receive items.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Create receiving record
            $receiving = PurchaseReceiving::create([
                'purchase_order_id' => $po->id,
                'receiving_date' => now(),
                'status' => PurchaseReceiving::STATUS_DRAFT,
                'notes' => 'Full receiving of all remaining items',
            ]);

            // Create receiving items for all remaining quantities
            foreach ($po->items as $poItem) {
                $remainingQty = $poItem->quantity - $poItem->quantity_received;
                if ($remainingQty > 0) {
                    PurchaseReceivingItem::create([
                        'purchase_receiving_id' => $receiving->id,
                        'purchase_order_item_id' => $poItem->id,
                        'product_id' => $poItem->product_id,
                        'quantity_received' => $remainingQty,
                        'unit_cost' => $poItem->unit_cost,
                    ]);
                }
            }

            // Confirm receiving
            $receiving->confirm();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'All items received successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to receive items.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}

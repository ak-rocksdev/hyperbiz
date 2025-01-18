<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Product;
use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Number;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\TransactionLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;

class TransactionController extends Controller
{
    /**
     * Display a list of transactions.
     */
    public function list(Request $request)
    {
        $search = $request->input('search');
        $perPage = $request->input('per_page', 5);

        $transactionsQuery = Transaction::with(['client', 'details.product'])->orderByDesc('created_at');

        if ($search) {
            $transactionsQuery->where(function ($query) use ($search) {
                $query->whereHas('client', function ($clientQuery) use ($search) {
                    $clientQuery->where('client_name', 'like', '%' . $search . '%');
                })
                ->orWhere('transaction_code', 'like', '%' . $search . '%')
                ->orWhere('transaction_type', 'like', '%' . $search . '%')
                ->orWhere('status', 'like', '%' . $search . '%')
                ->orWhereHas('details.product', function ($productQuery) use ($search) {
                    $productQuery->where('name', 'like', '%' . $search . '%');
                });
            });
        }

        $transactions = $transactionsQuery->paginate($perPage);

        $data = $transactions->getCollection()->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'transaction_code' => $transaction->transaction_code ?? 'N/A',
                'transaction_type' => $transaction->transaction_type,
                'client_name' => $transaction->client->client_name ?? 'N/A',
                'quantity' => $transaction->quantity,
                'grand_total' => $transaction->grand_total,
                'transaction_date' => Carbon::parse($transaction->transaction_date)->format('d M Y'),
                'status' => $transaction->status,
                'created_at' => Carbon::parse($transaction->created_at)->format('d M Y - H:i'),
            ];
        });

        // Calculate total counts
        $totalTransactionsCount = $transactions->count();

        // Calculate totals for purchases and sales
        $totalPurchaseValue = (float) Transaction::where('transaction_type', 'purchase')->sum('grand_total');
        $totalSellValue = (float) Transaction::where('transaction_type', 'sell')->sum('grand_total');

        // Products and clients
        $products = Product::pluck('name', 'id');
        $clients = Client::all()->map(function ($client) {
            return [
                'id' => $client->id,
                'client_name' => $client->client_name,
                'products' => $client->products,
            ];
        });

        $statuses = [
            'purchase' => collect(TransactionStatus::purchaseStatuses())->map(function ($status) {
                // return dd($status);
                return [
                    'value' => $status['value'],
                    'label' => $status['label'],
                ];
            })->toArray(),
            'sell' => collect(TransactionStatus::sellStatuses())->map(function ($status) {
                return [
                    'value' => $status['value'],
                    'label' => $status['label'],
                ];
            })->toArray(),
        ];

        return Inertia::render('Transaction/List', [
            'transactions' => $data,
            'pagination' => [
                'total' => $transactions->total(),
                'per_page' => $transactions->perPage(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
            ],
            'products' => $products,
            'clients' => $clients,
            'statuses' => $statuses,
            'resultCount' => $data->count(),
            'allTransactionsCount' => Transaction::count(),
            'totalTransactions' => $totalTransactionsCount,
            'totalPurchaseValue' => $totalPurchaseValue,
            'totalSellValue' => $totalSellValue,
        ]);
    }


    /**
     * Fetch transaction details via API.
     */
    public function detailApi($id)
    {
        $transaction = Transaction::with('details', 'client')->findOrFail($id);

        return response()->json([
            'transaction' => [
                'id' => $transaction->id,
                'transaction_code' => $transaction->transaction_code,
                'transaction_type' => $transaction->transaction_type,
                'client_name' => $transaction->client->client_name ?? 'N/A',
                'grand_total' => Number::currency($transaction->grand_total, in: 'IDR', locale: 'id'),
                'transaction_date' => Carbon::parse($transaction->transaction_date)->format('d M Y'),
                'status' => $transaction->status,
                'details' => $transaction->details->map(function ($detail) {
                    return [
                        'id' => $detail->id,
                        'name' => $detail->product->name,
                        'quantity' => $detail->quantity,
                        'price' => $detail->price,
                        'total_price' => Number::currency($detail->quantity * $detail->price, in: 'IDR', locale: 'id'),
                    ];
                }),
                'created_at' => Carbon::parse($transaction->created_at)->format('d M Y - H:i'),
            ],
        ]);
    }

    /**
     * Show the edit page for a transaction.
     */
    public function edit($id)
    {
        $transaction = Transaction::findOrFail($id);

        $data = [
            'id' => $transaction->id,
            'transaction_code' => $transaction->transaction_code,
            'transaction_type' => $transaction->transaction_type,
            'mst_client_id' => $transaction->mst_client_id,
            'client' => $transaction->client,
            'products' => $transaction->details->map(function ($detail) {
                return [
                    'id' => $detail->product->id,
                    'name' => $detail->product->name,
                    'quantity' => $detail->quantity,
                    'sku' => $detail->product->sku,
                    'price' => $detail->price,
                    'total_price' => $detail->quantity * $detail->price,
                ];
            }),
            'transaction_date' => Carbon::parse($transaction->transaction_date)->format('Y-m-d'),
            'grand_total' => $transaction->grand_total,
            'expedition_fee' => $transaction->expedition_fee,
            'status' => $transaction->status,
        ];

        $statuses = [
            'purchase' => collect(TransactionStatus::purchaseStatuses())->map(function ($status) {
                return [
                    'value' => $status['value'],
                    'label' => $status['label'],
                ];
            })->toArray(),
            'sell' => collect(TransactionStatus::sellStatuses())->map(function ($status) {
                return [
                    'value' => $status['value'],
                    'label' => $status['label'],
                ];
            })->toArray(),
        ];

        return Inertia::render('Transaction/Edit', [
            'transaction' => $data,
            'clients' => Client::with('products')->get(),
            'statuses' => $statuses,
        ]);
    }

    public function create()
    {
        $products = Product::where('is_active', 1)->pluck('name', 'id');
        $clients = Client::all()->map(function ($client) {
            return [
                'id' => $client->id,
                'client_name' => $client->client_name,
                'products' => $client->products->filter(function ($product) {
                    return $product->is_active == 1; // Ensure only active products are included
                })->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'description' => $product->description,
                        'sku' => $product->sku,
                        'barcode' => $product->barcode,
                        'price' => $product->price,
                        'cost_price' => $product->cost_price,
                        'currency' => $product->currency,
                        'stock_quantity' => $product->stock_quantity,
                        'min_stock_level' => $product->min_stock_level,
                        'weight' => $product->weight,
                        'dimensions' => $product->dimensions,
                        'image_url' => $product->image_url,
                    ];
                }),
            ];
        });
        
        $statuses = [
            'purchase' => TransactionStatus::purchaseStatuses(),
            'sell' => TransactionStatus::sellStatuses(),
        ];

        return Inertia::render('Transaction/Create', [
            'products' => $products,
            'clients' => $clients,
            'statuses' => $statuses,
        ]);
    }

    /**
     * Store a new transaction.
     */
    public function store(Request $request)
    {
        // Validate request data
        $validatedData = $request->validate(
            [
                'mst_client_id' => 'required|exists:mst_client,id',
                'transaction_type' => 'required|in:sell,purchase',
                'products' => 'required|array|min:1',
                'products.*.id' => 'required|exists:mst_products,id',
                'products.*.quantity' => 'required|integer|min:1',
                'products.*.price' => 'required|numeric|min:1',
                'transaction_date' => 'required|date',
                'expedition_fee' => 'nullable|numeric|min:0',
                'grand_total' => 'numeric',
            ],
            [],
            [
                'products.*.quantity' => 'product quantity',
                'products.*.id' => 'product ID',
                'products.*.price' => 'product price',
            ]
        );

        // Fetch all products at once
        $productIds = collect($validatedData['products'])->pluck('id');
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        // Check stock sufficiency and active status
        foreach ($validatedData['products'] as $product) {
            $productModel = $products[$product['id']];

            if (!$productModel->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => "Product is not active: {$productModel->name}",
                    'errors' => ["Product is not active: {$productModel->name}"],
                ], 422);
            }

            if (
                $validatedData['transaction_type'] === 'sell' &&
                $productModel->stock_quantity < $product['quantity']
            ) {
                return response()->json([
                    'success' => false,
                    'message' => "Insufficient stock for product: {$productModel->name}",
                    'errors' => ["Not enough stock for product: {$productModel->name}"],
                ], 422);
            }
        }

        // Wrap database operations in a transaction
        DB::transaction(function () use ($validatedData, $products) {
            // Create the transaction
            $transaction = Transaction::create([
                'transaction_code' => strtoupper(substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, 6)),
                'transaction_type' => $validatedData['transaction_type'],
                'mst_client_id' => $validatedData['mst_client_id'],
                'transaction_date' => $validatedData['transaction_date'] ?? now(),
                'grand_total' => $validatedData['grand_total'],
                'expedition_fee' => $validatedData['expedition_fee'] ?? 0,
                'status' => 'pending',
            ]);

            // Save transaction details and adjust stock
            foreach ($validatedData['products'] as $product) {
                $productModel = $products[$product['id']];

                $transaction->details()->create([
                    'mst_product_id' => $productModel->id,
                    'quantity' => $product['quantity'],
                    'price' => $product['price'],
                ]);

                // Adjust stock
                $adjustment = $validatedData['transaction_type'] === 'sell' ? -$product['quantity'] : $product['quantity'];
                $productModel->increment('stock_quantity', $adjustment);
            }
        });

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Transaction created successfully.',
        ], 201);
    }

    /**
     * Update an existing transaction.
     */
    public function update(Request $request, $id)
    {
        $transaction = Transaction::findOrFail($id);

        $validatedData = $request->validate([
            'transaction_code' => 'required|size:6',
            'transaction_type' => [
                'required',
                'in:sell,purchase',
                function ($attribute, $value, $fail) use ($transaction) {
                    if ($value !== $transaction->transaction_type) {
                        $fail('The Transaction Type cannot be changed. Use the "Remove" transaction to revert.');
                    }
                },
            ],
            'mst_client_id' => 'required|exists:mst_client,id',
            'grand_total' => 'required|numeric|min:0',
            'expedition_fee' => 'required|numeric|min:0',
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|exists:mst_products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.price' => 'required|numeric|min:0',
            'transaction_date' => 'required|date',
            'status' => 'required|in:pending,completed,canceled',
        ]);

        DB::beginTransaction();

        try {
            // Validate stock sufficiency for "sell"
            if ($validatedData['transaction_type'] === 'sell') {
                foreach ($validatedData['products'] as $product) {
                    $productModel = Product::findOrFail($product['id']);
                    $originalQuantity = $transaction->details->firstWhere('mst_product_id', $product['id'])->quantity ?? 0;
                    $availableStock = $productModel->stock_quantity + $originalQuantity;

                    if ($availableStock < $product['quantity']) {
                        return response()->json([
                            'success' => false,
                            'message' => "Insufficient stock for product: {$productModel->name}",
                            'errors' => ["Not enough stock for product: {$productModel->name}"],
                        ], 422);
                    }
                }
            }

            // Adjust stock levels
            $this->adjustStock($transaction, $validatedData['products'], $validatedData['transaction_type']);

            // Handle removed products
            foreach ($transaction->details as $detail) {
                if (!collect($validatedData['products'])->pluck('id')->contains($detail->mst_product_id)) {
                    $product = Product::findOrFail($detail->mst_product_id);
                    $product->increment('stock_quantity', $detail->quantity);
                }
            }

            // Recalculate grand total
            $grandTotal = collect($validatedData['products'])->sum(function ($product) {
                return $product['price'] * $product['quantity'];
            });

            // Update transaction
            $transaction->update([
                ...$validatedData,
                'grand_total' => $grandTotal,
            ]);

            // Update transaction details
            $transaction->details()->delete();
            foreach ($validatedData['products'] as $product) {
                $transaction->details()->create([
                    'mst_product_id' => $product['id'],
                    'quantity' => $product['quantity'],
                    'price' => $product['price'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaction updated successfully.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update the transaction.',
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }


    private function revertStock($transaction)
    {
        foreach ($transaction->details as $detail) {
            $product = Product::findOrFail($detail->mst_product_id);

            if ($transaction->transaction_type === 'sell') {
                // Reverse the stock decrement for a sell transaction
                $product->increment('stock_quantity', $detail->quantity);
            } 
            // For "purchase", no action is needed to revert stock
        }
    }

    private function adjustStock(Transaction $transaction, array $products, string $transactionType)
    {
        foreach ($products as $product) {
            $productModel = Product::findOrFail($product['id']);
            
            // Original quantity on the transaction detail (0 if not found)
            $originalQuantity = $transaction->details
                ->firstWhere('mst_product_id', $product['id'])
                ->quantity ?? 0;
            
            // Compare the old quantity to the new one
            // Positive $difference => increase in quantity
            // Negative $difference => decrease in quantity
            $difference = $product['quantity'] - $originalQuantity;
            
            // For a "sell" transaction, stock goes down; for a "purchase", stock goes up
            // If $difference is positive for "sell", you're selling more, so stock must decrease by that difference
            // If $difference is negative for "sell", you're selling fewer, so stock must increase by the absolute value of that difference
            // The inverse applies for "purchase"
            $adjustment = $transactionType === 'sell'
                ? -$difference // Negative means decrement for "sell"
                : $difference; // Positive means increment for "purchase"
            
            // Apply the adjustment to the stock
            $productModel->increment('stock_quantity', $adjustment);
        }
    }

    private function updateTransactionDetails($transaction, array $products)
    {
        foreach ($products as $product) {
            $transaction->details()->create([
                'mst_product_id' => $product['id'],
                'quantity' => $product['quantity'],
                'price' => $product['price'],
            ]);
        }
    }

    public function delete($id)
    {
        DB::beginTransaction();

        try {
            $transaction = Transaction::with('details')->findOrFail($id);

            // Collect log details for the transaction
            $logDetails = [
                'transaction_code' => $transaction->transaction_code,
                'transaction_type' => $transaction->transaction_type,
                'grand_total' => $transaction->grand_total,
                'expedition_fee' => $transaction->expedition_fee,
                'products' => $transaction->details->map(function ($detail) {
                    return [
                        'mst_product_id' => $detail->mst_product_id,
                        'quantity' => $detail->quantity,
                        'cost_price' => $detail->product->cost_price,
                        'price' => $detail->price,
                    ];
                })->toArray(),
            ];

            // Revert stock changes based on transaction type
            foreach ($transaction->details as $detail) {
                $product = Product::findOrFail($detail->mst_product_id);
                if ($transaction->transaction_type === 'sell') {
                    $product->increment('stock_quantity', $detail->quantity);
                } elseif ($transaction->transaction_type === 'purchase') {
                    $product->decrement('stock_quantity', $detail->quantity);
                }
            }

            // Delete transaction details and the transaction itself
            $transaction->details()->delete();
            $transaction->delete();

            // Log the deletion action
            $this->logTransactionAction(
                'delete',
                $logDetails,
                $transaction->id,
                'Transaction and associated details deleted successfully.'
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaction deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete transaction.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function previewPdf($id)
    {
        $transaction = Transaction::with('details', 'client')->findOrFail($id);

        $data = [
                'id' => $transaction->id,
                'transaction_code' => $transaction->transaction_code,
                'transaction_type' => $transaction->transaction_type,
                'client_name' => $transaction->client->client_name ?? 'N/A',
                'grand_total' => Number::currency($transaction->grand_total, in: 'IDR', locale: 'id'),
                'transaction_date' => Carbon::parse($transaction->transaction_date)->format('d M Y'),
                'status' => $transaction->status,
                'details' => $transaction->details->map(function ($detail) {
                    return [
                        'id' => $detail->id,
                        'name' => $detail->product->name,
                        'quantity' => $detail->quantity,
                        'price' => $detail->price,
                        'total_price' => Number::currency($detail->quantity * $detail->price, in: 'IDR', locale: 'id'),
                    ];
                }),
                'created_at' => Carbon::parse($transaction->created_at)->format('d M Y - H:i'),
            ];

        return Inertia::render('Transaction/Pdf', [
            'transaction' => $data,
        ]);
    }

    public function exportPdf($id)
    {
        $transaction = Transaction::with(['details.product'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.transaction', compact('transaction'));
        
        return $pdf->stream('transaction-details.pdf');
        // return view('pdf.transaction', compact('transaction'));

        // return Inertia::render('Transaction/ExportPdf', [
        //     'transaction' => $transaction,
        // ]);
        
        
        // return $pdf->download('transaction-details.pdf');
    }
    
    private function logTransactionAction(
        string $action,
        ?array $changedFields = null,
        ?int $transactionId = null,
        ?string $remarks = null
    ) {
        TransactionLog::create([
            'transaction_id' => $transactionId,
            'action' => $action,
            'changed_fields' => $changedFields,
            'user_id' => Auth::id(), // Automatically log the current user
            'actor_role' => Auth::user()->role ?? null, // Use the role if available
            'ip_address' => request()->ip(),
            'user_agent' => request()->header('User-Agent'),
            'remarks' => $remarks,
        ]);
    }
}

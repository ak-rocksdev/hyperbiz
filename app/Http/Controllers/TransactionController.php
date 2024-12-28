<?php

namespace App\Http\Controllers;

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

class TransactionController extends Controller
{
    /**
     * Display a list of transactions.
     */
    public function list()
    {
        $transactions = Transaction::with('client')->orderByDesc('created_at')->get();

        $data = $transactions->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'transaction_code' => $transaction->transaction_code ?? 'N/A',
                'client_name' => $transaction->client->client_name ?? 'N/A',
                'quantity' => $transaction->quantity,
                'grand_total' => $transaction->grand_total,
                'transaction_date' => Carbon::parse($transaction->transaction_date)->format('d M Y'),
                'created_at' => Carbon::parse($transaction->created_at)->format('d M Y - H:i'),
            ];
        });

        $totalTransactionsCount = $transactions->count();

        // get total transaction value
        $totalTransactionValue = $transactions->sum('grand_total');


        $products = Product::pluck('name', 'id');
        $clients = Client::all()->map(function ($client) {
            return [
                'id' => $client->id,
                'client_name' => $client->client_name,
                'products' => $client->products,
            ];
        });

        return Inertia::render('Transaction/List', [
            'transactions' => $data,
            'products' => $products,
            'clients' => $clients,
            'totalTransactions' => $totalTransactionsCount,
            'totalTransactionValue' => $totalTransactionValue,
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
     * Store a new transaction.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'mst_client_id' => 'required|exists:mst_client,id',
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|exists:mst_products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.price' => 'required|numeric|min:1',
            'transaction_date' => 'required|date',
            'expedition_fee' => 'nullable|numeric|min:0',
            'grand_total' => 'numeric',
        ], [], [
            'products.*.quantity' => 'product quantity',
            'products.*.id' => 'product ID',
            'products.*.price' => 'product price',
        ]);
    
        // Save the transaction
        $transaction = Transaction::create([
            'transaction_code' => strtoupper(substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, 6)),
            'mst_client_id' => $validatedData['mst_client_id'],
            'transaction_date' => $validatedData['transaction_date'] ?? now(),
            'grand_total' => $validatedData['grand_total'],
            'expedition_fee' => $validatedData['expedition_fee'] ?? 0,
            'status' => 'pending'
        ]);
    
        // Save transaction details
        foreach ($validatedData['products'] as $product) {
            TransactionDetail::create([
                'transaction_id' => $transaction->id,
                'mst_product_id' => $product['id'],
                'quantity' => $product['quantity'],
                'price' => $product['price'],
            ]);
    
            // Decrease stock quantity
            Product::where('id', $product['id'])->decrement('stock_quantity', $product['quantity']);
        }

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

        // Validate the incoming data
        $validatedData = $request->validate([
            'id' => 'required|exists:transactions,id',
            'mst_client_id' => 'required|exists:mst_client,id',
            'grand_total' => 'required|numeric|min:0',
            'expedition_fee' => 'required|numeric|min:0',
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|exists:mst_products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.stock_quantity' => 'required|integer',
            'products.*.total_price' => 'required|numeric|min:0',
            'transaction_date' => 'required|date',
        ]);

        DB::beginTransaction();

        try {
            // Record the original data for comparison
            $originalTransaction = $transaction->toArray();
            $originalDetails = $transaction->details->map(function ($detail) {
                return [
                    'mst_product_id' => $detail->mst_product_id,
                    'quantity' => $detail->quantity,
                    'price' => $detail->price,
                ];
            })->toArray();

            // Revert stock changes of the previous transaction
            foreach ($transaction->details as $detail) {
                $product = Product::findOrFail($detail->mst_product_id);
                $product->increment('stock_quantity', $detail->quantity);
            }

            // Update stock for the new transaction
            foreach ($validatedData['products'] as $product) {
                $productModel = Product::findOrFail($product['id']);

                // Check if sufficient stock exists after decrement
                if ($productModel->stock_quantity < $product['quantity']) {
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient stock for product: {$productModel->name}",
                        'errors' => [
                            "Not enough stock for product: {$productModel->name}",
                        ],
                    ], 442);
                }

                // Decrement stock
                $productModel->decrement('stock_quantity', $product['quantity']);
            }

            // Update the transaction
            $transaction->update([
                'mst_client_id' => $validatedData['mst_client_id'],
                'transaction_date' => $validatedData['transaction_date'],
                'grand_total' => $validatedData['grand_total'],
                'expedition_fee' => $validatedData['expedition_fee'],
            ]);

            // Update transaction details
            $transaction->details()->delete(); // Remove old details
            foreach ($validatedData['products'] as $product) {
                $productModel = Product::findOrFail($product['id']);

                $transaction->details()->create([
                    'mst_product_id' => $product['id'],
                    'quantity' => $product['quantity'],
                    'price' => $productModel->price,
                ]);
            }

            // Record the updated data for comparison
            $updatedTransaction = $transaction->toArray();
            $updatedDetails = $validatedData['products'];

            // Prepare changes to log
            $changedFields = [];
            foreach ($originalTransaction as $key => $value) {
                if (isset($updatedTransaction[$key]) && $updatedTransaction[$key] != $value) {
                    $changedFields[$key] = ['old' => $value, 'new' => $updatedTransaction[$key]];
                }
            }

            $detailChanges = [];
            foreach ($originalDetails as $originalDetail) {
                if (!isset($originalDetail['mst_product_id'])) {
                    continue; // Skip if mst_product_id is not set
                }

                $updatedDetail = collect($updatedDetails)->firstWhere('id', $originalDetail['mst_product_id']);
                if ($updatedDetail) {
                    foreach ($originalDetail as $key => $value) {
                        if (isset($updatedDetail[$key]) && $updatedDetail[$key] != $value) {
                            $detailChanges[] = [
                                'product_id' => $originalDetail['mst_product_id'],
                                'field' => $key,
                                'old' => $value,
                                'new' => $updatedDetail[$key],
                            ];
                        }
                    }
                }
            }

            // Log the changes
            $this->logTransactionAction(
                'update',
                [
                    'transaction_changes' => $changedFields,
                    'detail_changes' => $detailChanges,
                ],
                $transaction->id,
                'Transaction updated with changes logged.'
            );

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
                'errors' => [
                    $e->getMessage(),
                ],
            ], 500);
        }
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

        return Inertia::render('Transaction/Edit', [
            'transaction' => $data,
            'clients' => Client::with('products')->get(),
        ]);
    }

    public function create()
    {
        $products = Product::pluck('name', 'id');
        $clients = Client::all()->map(function ($client) {
            return [
                'id' => $client->id,
                'client_name' => $client->client_name,
                'products' => $client->products->map(function ($product) {
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
                        'is_active' => $product->is_active,
                    ];
                }),
            ];
        });

        return Inertia::render('Transaction/Create', [
            'products' => $products,
            'clients' => $clients,
        ]);
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

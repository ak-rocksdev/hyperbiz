<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Product;
use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Number;

class TransactionController extends Controller
{
    /**
     * Display a list of transactions.
     */
    public function list()
    {
        $transactions = Transaction::with('product', 'client')->orderByDesc('created_at')->get();

        $data = $transactions->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'product_name' => $transaction->product->name ?? 'N/A',
                'client_name' => $transaction->client->client_name ?? 'N/A',
                'quantity' => $transaction->quantity,
                'total_price' => $transaction->total_price,
                'transaction_date' => Carbon::parse($transaction->transaction_date)->format('d M Y - H:i'),
                'created_at' => Carbon::parse($transaction->created_at)->format('d M Y - H:i'),
            ];
        });

        $totalTransactions = $transactions->count();

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
            'totalTransactions' => $totalTransactions,
        ]);
    }

    /**
     * Fetch transaction details via API.
     */
    public function detailApi($id)
    {
        $transaction = Transaction::with('product', 'client')->findOrFail($id);

        return response()->json([
            'transaction' => [
                'id' => $transaction->id,
                'product_name' => $transaction->product->name ?? 'N/A',
                'client_name' => $transaction->client->client_name ?? 'N/A',
                'quantity' => $transaction->quantity,
                'total_price' => Number::currency($transaction->total_price, in: 'IDR', locale: 'id'),
                'transaction_date' => Carbon::parse($transaction->transaction_date)->format('d M Y - H:i'),
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
            'product_id' => 'required|exists:products,id',
            'client_id' => 'required|exists:clients,id',
            'quantity' => 'required|integer|min:1',
            'total_price' => 'required|numeric|min:0',
            'transaction_date' => 'required|date',
        ]);

        Transaction::create($validatedData);

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
            'mst_product_id' => 'required|exists:mst_products,id',
            'mst_client_id' => 'required|exists:mst_clients,id',
            'value' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1',
            'total_price' => 'required|numeric|min:0',
            'transaction_date' => 'required|date',
        ]);

        $transaction->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Transaction updated successfully.',
        ], 200);
    }

    /**
     * Show the edit page for a transaction.
     */
    public function edit($id)
    {
        $transaction = Transaction::findOrFail($id);

        $data = [
            'id' => $transaction->id,
            'product_id' => $transaction->product_id,
            'client_id' => $transaction->client_id,
            'quantity' => $transaction->quantity,
            'total_price' => $transaction->total_price,
            'transaction_date' => $transaction->transaction_date,
        ];

        $products = Product::pluck('name', 'id');
        $clients = Client::pluck('client_name', 'id');

        return Inertia::render('Transaction/Edit', [
            'transaction' => $data,
            'products' => $products,
            'clients' => $clients,
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
}

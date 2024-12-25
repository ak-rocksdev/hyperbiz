<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Brand;
use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Number;

class ProductController extends Controller
{
    /**
     * Display a list of products.
     */
    public function list()
    {
        $products = Product::with('category', 'brand', 'client')->get();

        $data = $products->map(function ($product) {
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
                'category' => $product->category->name ?? 'N/A',
                'brand' => $product->brand->name ?? 'N/A',
                'client' => $product->client->client_name ?? 'N/A',
                'weight' => $product->weight,
                'dimensions' => $product->dimensions,
                'image_url' => $product->image_url,
                'created_at' => Carbon::parse($product->created_at)->format('d M Y - H:i'),
            ];
        });

        $totalProducts = $products->count();

        $categories = ProductCategory::pluck('name', 'id');
        $brands = Brand::pluck('name', 'id');
        $clients = Client::pluck('client_name', 'id');

        return Inertia::render('Product/List', [
            'products' => $data,
            'clients' => $clients,
            'categories' => $categories,
            'brands' => $brands,
            'totalProducts' => $totalProducts,
        ]);
    }

    /**
     * Fetch product details via API.
     */
    public function detailApi($id)
    {
        $product = Product::with('category', 'brand')->findOrFail($id);

        return response()->json([
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'sku' => $product->sku,
                'barcode' => $product->barcode ?? 'N/A',
                'price' => Number::currency($product->price, in: $product->currency, locale: 'id'),
                'cost_price' => Number::currency($product->cost_price, in: $product->currency, locale: 'id'),
                'stock_quantity' => $product->stock_quantity,
                'min_stock_level' => $product->min_stock_level,
                'category' => $product->category->name ?? 'N/A',
                'brand' => $product->brand->name ?? 'N/A',
                'client' => $product->client->client_name ?? 'N/A',
                'weight' => $product->weight ?? 'N/A',
                'dimensions' => $product->dimensions ?? 'N/A',
                'image_url' => $product->image_url,
                'is_active' => $product->is_active,
                'created_at' => Carbon::parse($product->created_at)->format('d M Y - H:i'),
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
            'sku' => 'nullable|string|max:100|unique:products,sku',
            'barcode' => 'nullable|string|max:100',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'stock_quantity' => 'required|integer|min:0',
            'min_stock_level' => 'nullable|integer|min:0',
            'mst_product_category_id' => 'nullable|exists:mst_product_categories,id',
            'mst_brand_id' => 'nullable|exists:mst_brands,id',
            'mst_client_id' => 'required|exists:mst_clients,id',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|string',
            'image_url' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        Product::create($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully.',
        ], 201);
    }

    /**
     * Update an existing product.
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products,sku,' . $product->id,
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'product_category_id' => 'required|exists:product_categories,id',
            'brand_id' => 'nullable|exists:brands,id',
        ]);

        $validatedData['updated_by'] = auth()->id();

        $product->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully.',
        ], 200);
    }
}

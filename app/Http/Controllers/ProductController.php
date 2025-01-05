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
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display a list of products.
     */
    public function list()
    {
        $products = Product::with('category', 'brand', 'client')->orderByDesc('created_at')->get();

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
                'is_active' => $product->is_active,
            ];
        });

        $totalProducts = $products->count();
        $totalCategoriesCount = Product::distinct('mst_product_category_id')->count('mst_product_category_id');

        $categories = ProductCategory::pluck('name', 'id');
        $brands = Brand::pluck('name', 'id');
        $clients = Client::pluck('client_name', 'id');

        return Inertia::render('Product/List', [
            'products' => $data,
            'clients' => $clients,
            'categories' => $categories,
            'brands' => $brands,
            'totalProducts' => $totalProducts,
            'totalCategoriesCount' => $totalCategoriesCount,
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
                'sku' => $product->sku ?? 'N/A',
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
            'sku' => 'nullable|string|max:100|unique:mst_products,sku',
            'barcode' => 'nullable|string|max:100',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'stock_quantity' => 'nullable|integer|min:0',
            'min_stock_level' => 'nullable|integer|min:0',
            'mst_product_category_id' => 'nullable|exists:mst_product_categories,id',
            'mst_brand_id' => 'nullable|exists:mst_brands,id',
            'mst_client_id' => 'required|exists:mst_client,id',
            'weight' => 'required|numeric|min:0',
            'dimensions' => 'nullable|string',
            'image_url' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        DB::beginTransaction();
        try {
            if (!isset($validatedData['stock_quantity'])) {
                $validatedData['stock_quantity'] = 0;
            }

            Product::create($validatedData);

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
                'error' => $e->getMessage(),
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
            'cost_price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'stock_quantity' => 'required|integer|min:0',
            'min_stock_level' => 'nullable|integer|min:0',
            'mst_product_category_id' => 'nullable|exists:mst_product_categories,id',
            'mst_brand_id' => 'nullable|exists:mst_brands,id',
            'mst_client_id' => 'required|exists:mst_client,id',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|string',
            'image_url' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $product->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully.',
        ], 200);
    }

    /**
     * Show the edit page for a product.
     */
    public function edit($id)
    {
        $product = Product::findOrFail($id);

        $data = [
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
            'mst_product_category_id' => $product->mst_product_category_id,
            'mst_brand_id' => $product->mst_brand_id,
            'mst_client_id' => $product->mst_client_id,
            'weight' => $product->weight,
            'dimensions' => $product->dimensions,
            'image_url' => $product->image_url,
            'is_active' => $product->is_active,
        ];

        $productCategories = ProductCategory::pluck('name', 'id');

        $brands = Brand::pluck('name', 'id');

        $clients = Client::pluck('client_name', 'id');

        return Inertia::render('Product/Edit', [
            'product' => $data,
            'productCategories' => $productCategories,
            'brands' => $brands,
            'clients' => $clients,
        ]);
    }
}

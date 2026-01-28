<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Brand;
use Inertia\Inertia;
use Carbon\Carbon;

class BrandController extends Controller
{
    public function list(Request $request)
    {
        $query = Brand::withCount('products');

        // Search filter
        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Pagination
        $perPage = $request->input('per_page', 10);
        $brands = $query->orderByDesc('created_at')->paginate($perPage);

        $data = $brands->map(fn($brand) => [
            'id' => $brand->id,
            'name' => $brand->name,
            'products_count' => $brand->products_count,
            'created_at' => Carbon::parse($brand->created_at)->format('d M Y'),
        ]);

        return Inertia::render('Brand/List', [
            'brands' => $data,
            'pagination' => [
                'total' => $brands->total(),
                'per_page' => $brands->perPage(),
                'current_page' => $brands->currentPage(),
                'last_page' => $brands->lastPage(),
                'from' => $brands->firstItem(),
                'to' => $brands->lastItem(),
            ],
            'filters' => [
                'search' => $request->search,
            ],
            'stats' => [
                'total_brands' => Brand::count(),
            ],
        ]);
    }

    // Show brand detail page
    public function show($id)
    {
        $brand = Brand::withCount('products')->findOrFail($id);
        $products = $brand->products()
            ->with('category')
            ->select('id', 'name', 'sku', 'mst_product_category_id', 'price')
            ->limit(10)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'category_name' => $p->category?->name ?? '-',
                'price' => $p->price,
            ]);

        return Inertia::render('Brand/Detail', [
            'brand' => [
                'id' => $brand->id,
                'name' => $brand->name,
                'products_count' => $brand->products_count,
                'created_at' => Carbon::parse($brand->created_at)->format('d M Y H:i'),
                'updated_at' => $brand->updated_at ? Carbon::parse($brand->updated_at)->format('d M Y H:i') : null,
            ],
            'products' => $products,
        ]);
    }

    // Show edit page
    public function edit($id)
    {
        $brand = Brand::withCount('products')->findOrFail($id);

        return Inertia::render('Brand/Edit', [
            'brand' => [
                'id' => $brand->id,
                'name' => $brand->name,
                'products_count' => $brand->products_count,
            ],
        ]);
    }

    // Get brand details via API
    public function detailApi($id)
    {
        $brand = Brand::findOrFail($id);

        return response()->json([
            'brand' => [
                'id' => $brand->id,
                'name' => $brand->name,
                'created_at' => Carbon::parse($brand->created_at)->format('d M Y - H:i'),
            ],
        ]);
    }

    // Store a new brand
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:mst_brands,name',
        ]);

        $brand = Brand::create($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Brand created successfully.',
        ], 201);
    }

    // Update brand
    public function update(Request $request, $id)
    {
        $brand = Brand::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:mst_brands,name,' . $id,
        ]);

        $brand->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Brand updated successfully.',
        ]);
    }
}

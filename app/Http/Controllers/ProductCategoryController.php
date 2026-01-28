<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\ProductCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProductCategoryController extends Controller
{
    public function list(Request $request)
    {
        $query = ProductCategory::with('parent')->withCount('products');

        // Search filter
        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Pagination
        $perPage = $request->input('per_page', 10);
        $productCategories = $query->orderByDesc('created_at')->paginate($perPage);

        $data = $productCategories->map(fn($category) => [
            'id' => $category->id,
            'name' => $category->name,
            'parent_id' => $category->parent_id,
            'parent_name' => $category->parent?->name ?? '-',
            'products_count' => $category->products_count,
            'created_at' => Carbon::parse($category->created_at)->format('d M Y'),
        ]);

        // Get all categories for parent dropdown
        $parentOptions = ProductCategory::select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn($c) => [
                'value' => $c->id,
                'label' => $c->name,
            ]);

        return Inertia::render('ProductCategory/List', [
            'productCategories' => $data,
            'parentOptions' => $parentOptions,
            'pagination' => [
                'total' => $productCategories->total(),
                'per_page' => $productCategories->perPage(),
                'current_page' => $productCategories->currentPage(),
                'last_page' => $productCategories->lastPage(),
                'from' => $productCategories->firstItem(),
                'to' => $productCategories->lastItem(),
            ],
            'filters' => [
                'search' => $request->search,
            ],
            'stats' => [
                'total_categories' => ProductCategory::count(),
            ],
        ]);
    }

    // Show category detail page
    public function show($id)
    {
        $category = ProductCategory::with('parent')
            ->withCount('products')
            ->findOrFail($id);

        // Get products in this category (limited to 10)
        $products = $category->products()
            ->with('brand')
            ->select('id', 'name', 'sku', 'mst_brand_id', 'price')
            ->limit(10)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'brand_name' => $p->brand?->name ?? '-',
                'price' => $p->price,
            ]);

        // Get child categories
        $childCategories = ProductCategory::where('parent_id', $id)
            ->withCount('products')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'products_count' => $c->products_count,
            ]);

        return Inertia::render('ProductCategory/Detail', [
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'parent_id' => $category->parent_id,
                'parent_name' => $category->parent?->name ?? null,
                'products_count' => $category->products_count,
                'created_at' => Carbon::parse($category->created_at)->format('d M Y H:i'),
                'updated_at' => $category->updated_at
                    ? Carbon::parse($category->updated_at)->format('d M Y H:i')
                    : null,
            ],
            'products' => $products,
            'childCategories' => $childCategories,
        ]);
    }

    // Show edit page
    public function edit($id)
    {
        $category = ProductCategory::with('parent')
            ->withCount('products')
            ->findOrFail($id);

        // Get all categories except self for parent dropdown
        $parentOptions = ProductCategory::where('id', '!=', $id)
            ->where(function ($query) use ($id) {
                $query->whereNull('parent_id')
                      ->orWhere('parent_id', '!=', $id);
            })
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn($c) => [
                'value' => $c->id,
                'label' => $c->name,
            ]);

        return Inertia::render('ProductCategory/Edit', [
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'parent_id' => $category->parent_id,
                'parent_name' => $category->parent?->name ?? null,
                'products_count' => $category->products_count,
            ],
            'parentOptions' => $parentOptions,
        ]);
    }

    // Get product Category details via API
    public function detailApi($id)
    {
        $productCategory = ProductCategory::with('parent')->findOrFail($id);

        return response()->json([
            'productCategory' => [
                'id' => $productCategory->id,
                'name' => $productCategory->name,
                'parent_id' => $productCategory->parent_id,
                'parent_name' => $productCategory->parent?->name ?? null,
                'created_at' => Carbon::parse($productCategory->created_at)->format('d M Y - H:i'),
            ],
        ]);
    }

    // Store a new product Category
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:mst_product_categories,name',
            'parent_id' => 'nullable|exists:mst_product_categories,id',
        ]);

        DB::beginTransaction();
        try {
            $productCategory = ProductCategory::create($validatedData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product Category created successfully.',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create product category',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Update product category
    public function update(Request $request, $id)
    {
        $productCategory = ProductCategory::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:mst_product_categories,name,' . $id,
            'parent_id' => 'nullable|exists:mst_product_categories,id|different:id',
        ]);

        DB::beginTransaction();
        try {
            $productCategory->update($validatedData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product Category updated successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update product category',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Delete product category
    public function destroy($id)
    {
        $category = ProductCategory::withCount('products')->findOrFail($id);

        // Check if category has products
        if ($category->products_count > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete category. It has {$category->products_count} product(s) assigned.",
            ], 422);
        }

        // Check if category has child categories
        $childCount = ProductCategory::where('parent_id', $id)->count();
        if ($childCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete category. It has {$childCount} child category/categories.",
            ], 422);
        }

        DB::beginTransaction();
        try {
            $category->delete();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete category.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

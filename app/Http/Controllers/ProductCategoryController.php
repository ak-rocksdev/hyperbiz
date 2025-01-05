<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\ProductCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProductCategoryController extends Controller
{
    public function list()
    {
        $productCategories = ProductCategory::orderByDesc('created_at')->get();

        $data = $productCategories->map(function ($productCategory) {
            return [
                'id' => $productCategory->id,
                'name' => $productCategory->name,
                'parent' => $productCategory->parent ? $productCategory->parent->name : '-',
                'created_at' => Carbon::parse($productCategory->created_at)->format('d M Y - H:i'),
            ];
        });

        $totalProductCategories = $productCategories->count();

        return Inertia::render('ProductCategory/List', [
            'productCategories' => $data,
            'parents' => ProductCategory::pluck('name', 'id'),
            'totalProductCategories' => $totalProductCategories,
        ]);
    }

    // Get product Category details via API
    public function detailApi($id)
    {
        $productCategory = ProductCategory::findOrFail($id);

        return response()->json([
            'productCategory' => [
                'id' => $productCategory->id,
                'name' => $productCategory->name,
                'parent' => $productCategory->parent,
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

    public function update(Request $request, $id)
    {
        $productCategory = ProductCategory::findOrFail($id);

        $validatedData = $request->validate([
            'id' => 'required|exists:mst_product_categories,id',
            'name' => 'required|string|max:255|unique:mst_product_categories,name,' . $productCategory->id,
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
}

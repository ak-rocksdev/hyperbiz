<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\UomCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UomCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:uom.view'], ['only' => ['list', 'show', 'detailApi']]);
        $this->middleware(['permission:uom.create'], ['only' => ['store']]);
        $this->middleware(['permission:uom.edit'], ['only' => ['edit', 'update']]);
        $this->middleware(['permission:uom.delete'], ['only' => ['destroy']]);
    }

    public function list(Request $request)
    {
        $query = UomCategory::withCount('uoms');

        // Search filter
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%');
            });
        }

        // Status filter
        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', $request->status === 'active');
        }

        // Pagination
        $perPage = $request->input('per_page', 10);
        $categories = $query->orderBy('code')->paginate($perPage);

        $data = $categories->map(fn($category) => [
            'id' => $category->id,
            'code' => $category->code,
            'name' => $category->name,
            'description' => $category->description,
            'is_active' => $category->is_active,
            'uoms_count' => $category->uoms_count,
            'created_at' => Carbon::parse($category->created_at)->format('d M Y'),
        ]);

        return Inertia::render('UomCategory/List', [
            'categories' => $data,
            'pagination' => [
                'total' => $categories->total(),
                'per_page' => $categories->perPage(),
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'from' => $categories->firstItem(),
                'to' => $categories->lastItem(),
            ],
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
            ],
            'stats' => [
                'total_categories' => UomCategory::count(),
                'active_categories' => UomCategory::where('is_active', true)->count(),
            ],
        ]);
    }

    public function show($id)
    {
        $category = UomCategory::with(['uoms' => function ($query) {
            $query->orderBy('code');
        }])->withCount('uoms')->findOrFail($id);

        return Inertia::render('UomCategory/Detail', [
            'category' => [
                'id' => $category->id,
                'code' => $category->code,
                'name' => $category->name,
                'description' => $category->description,
                'is_active' => $category->is_active,
                'uoms_count' => $category->uoms_count,
                'created_at' => Carbon::parse($category->created_at)->format('d M Y H:i'),
                'updated_at' => $category->updated_at
                    ? Carbon::parse($category->updated_at)->format('d M Y H:i')
                    : null,
            ],
            'uoms' => $category->uoms->map(fn($uom) => [
                'id' => $uom->id,
                'code' => $uom->code,
                'name' => $uom->name,
                'conversion_factor' => $uom->conversion_factor,
                'is_base' => $uom->base_uom_id === null,
                'is_active' => $uom->is_active,
            ]),
        ]);
    }

    public function edit($id)
    {
        $category = UomCategory::withCount('uoms')->findOrFail($id);

        return Inertia::render('UomCategory/Edit', [
            'category' => [
                'id' => $category->id,
                'code' => $category->code,
                'name' => $category->name,
                'description' => $category->description,
                'is_active' => $category->is_active,
                'uoms_count' => $category->uoms_count,
            ],
        ]);
    }

    public function detailApi($id)
    {
        $category = UomCategory::withCount('uoms')->findOrFail($id);

        return response()->json([
            'category' => [
                'id' => $category->id,
                'code' => $category->code,
                'name' => $category->name,
                'description' => $category->description,
                'is_active' => $category->is_active,
                'uoms_count' => $category->uoms_count,
                'created_at' => Carbon::parse($category->created_at)->format('d M Y - H:i'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'code' => 'required|string|max:20|unique:mst_uom_categories,code',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            $category = UomCategory::create([
                'code' => strtoupper($validatedData['code']),
                'name' => $validatedData['name'],
                'description' => $validatedData['description'] ?? null,
                'is_active' => $validatedData['is_active'] ?? true,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'UoM Category created successfully.',
                'category' => $category,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create UoM category',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $category = UomCategory::findOrFail($id);

        $validatedData = $request->validate([
            'code' => 'required|string|max:20|unique:mst_uom_categories,code,' . $id,
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            $category->update([
                'code' => strtoupper($validatedData['code']),
                'name' => $validatedData['name'],
                'description' => $validatedData['description'] ?? null,
                'is_active' => $validatedData['is_active'] ?? true,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'UoM Category updated successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update UoM category',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $category = UomCategory::withCount('uoms')->findOrFail($id);

        // Check if category has UoMs
        if ($category->uoms_count > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete category. It has {$category->uoms_count} UoM(s) assigned.",
            ], 422);
        }

        DB::beginTransaction();
        try {
            $category->delete();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'UoM Category deleted successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete UoM category.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function toggleStatus($id)
    {
        $category = UomCategory::findOrFail($id);

        DB::beginTransaction();
        try {
            $category->update(['is_active' => !$category->is_active]);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully.',
                'is_active' => $category->is_active,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update status.',
            ], 500);
        }
    }
}

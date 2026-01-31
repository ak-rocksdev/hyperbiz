<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Uom;
use App\Models\UomCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UomController extends Controller
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
        $query = Uom::with(['category', 'baseUom'])
            ->withCount('products')
            ->withCount('productUoms');

        // Search filter
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%');
            });
        }

        // Category filter
        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        // Status filter
        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', $request->status === 'active');
        }

        // Pagination
        $perPage = $request->input('per_page', 10);
        $uoms = $query->orderBy('category_id')->orderBy('code')->paginate($perPage);

        $data = $uoms->map(fn($uom) => [
            'id' => $uom->id,
            'code' => $uom->code,
            'name' => $uom->name,
            'category_id' => $uom->category_id,
            'category_name' => $uom->category?->name ?? '-',
            'base_uom_id' => $uom->base_uom_id,
            'base_uom_code' => $uom->baseUom?->code ?? '-',
            'conversion_factor' => $uom->conversion_factor,
            'is_base' => $uom->base_uom_id === null,
            'is_active' => $uom->is_active,
            'products_count' => $uom->products_count,
            'product_uoms_count' => $uom->product_uoms_count,
            'created_at' => Carbon::parse($uom->created_at)->format('d M Y'),
        ]);

        // Get categories for filter dropdown
        $categories = UomCategory::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn($c) => [
                'value' => $c->id,
                'label' => $c->name,
            ]);

        return Inertia::render('Uom/List', [
            'uoms' => $data,
            'categories' => $categories,
            'pagination' => [
                'total' => $uoms->total(),
                'per_page' => $uoms->perPage(),
                'current_page' => $uoms->currentPage(),
                'last_page' => $uoms->lastPage(),
                'from' => $uoms->firstItem(),
                'to' => $uoms->lastItem(),
            ],
            'filters' => [
                'search' => $request->search,
                'category_id' => $request->category_id,
                'status' => $request->status,
            ],
            'stats' => [
                'total_uoms' => Uom::count(),
                'active_uoms' => Uom::where('is_active', true)->count(),
                'base_uoms' => Uom::whereNull('base_uom_id')->count(),
            ],
        ]);
    }

    public function show($id)
    {
        $uom = Uom::with(['category', 'baseUom', 'derivedUoms'])
            ->withCount(['products', 'productUoms'])
            ->findOrFail($id);

        return Inertia::render('Uom/Detail', [
            'uom' => [
                'id' => $uom->id,
                'code' => $uom->code,
                'name' => $uom->name,
                'description' => $uom->description,
                'category_id' => $uom->category_id,
                'category_name' => $uom->category?->name ?? '-',
                'base_uom_id' => $uom->base_uom_id,
                'base_uom_code' => $uom->baseUom?->code ?? '-',
                'base_uom_name' => $uom->baseUom?->name ?? '-',
                'conversion_factor' => $uom->conversion_factor,
                'is_base' => $uom->base_uom_id === null,
                'is_active' => $uom->is_active,
                'products_count' => $uom->products_count,
                'product_uoms_count' => $uom->product_uoms_count,
                'created_at' => Carbon::parse($uom->created_at)->format('d M Y H:i'),
                'updated_at' => $uom->updated_at
                    ? Carbon::parse($uom->updated_at)->format('d M Y H:i')
                    : null,
            ],
            'derivedUoms' => $uom->derivedUoms->map(fn($derived) => [
                'id' => $derived->id,
                'code' => $derived->code,
                'name' => $derived->name,
                'conversion_factor' => $derived->conversion_factor,
                'is_active' => $derived->is_active,
            ]),
        ]);
    }

    public function edit($id)
    {
        $uom = Uom::with(['category', 'baseUom'])
            ->withCount(['products', 'productUoms'])
            ->findOrFail($id);

        // Get categories for dropdown
        $categories = UomCategory::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn($c) => [
                'value' => $c->id,
                'label' => $c->name,
            ]);

        // Get potential base UoMs (same category, excluding self)
        $baseUomOptions = [];
        if ($uom->category_id) {
            $baseUomOptions = Uom::where('category_id', $uom->category_id)
                ->where('id', '!=', $id)
                ->whereNull('base_uom_id') // Only base UoMs can be referenced
                ->where('is_active', true)
                ->orderBy('code')
                ->get()
                ->map(fn($u) => [
                    'value' => $u->id,
                    'label' => "{$u->code} - {$u->name}",
                ]);
        }

        return Inertia::render('Uom/Edit', [
            'uom' => [
                'id' => $uom->id,
                'code' => $uom->code,
                'name' => $uom->name,
                'description' => $uom->description,
                'category_id' => $uom->category_id,
                'base_uom_id' => $uom->base_uom_id,
                'conversion_factor' => $uom->conversion_factor,
                'is_active' => $uom->is_active,
                'products_count' => $uom->products_count,
                'product_uoms_count' => $uom->product_uoms_count,
            ],
            'categories' => $categories,
            'baseUomOptions' => $baseUomOptions,
        ]);
    }

    public function detailApi($id)
    {
        $uom = Uom::with(['category', 'baseUom'])->findOrFail($id);

        return response()->json([
            'uom' => [
                'id' => $uom->id,
                'code' => $uom->code,
                'name' => $uom->name,
                'description' => $uom->description,
                'category_id' => $uom->category_id,
                'category_name' => $uom->category?->name ?? '-',
                'base_uom_id' => $uom->base_uom_id,
                'base_uom_code' => $uom->baseUom?->code ?? '-',
                'conversion_factor' => $uom->conversion_factor,
                'is_base' => $uom->base_uom_id === null,
                'is_active' => $uom->is_active,
                'created_at' => Carbon::parse($uom->created_at)->format('d M Y - H:i'),
            ],
        ]);
    }

    public function getBaseUomsByCategory($categoryId)
    {
        $baseUoms = Uom::where('category_id', $categoryId)
            ->whereNull('base_uom_id')
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->map(fn($u) => [
                'value' => $u->id,
                'label' => "{$u->code} - {$u->name}",
            ]);

        return response()->json(['baseUoms' => $baseUoms]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'code' => 'required|string|max:10|unique:mst_uom,code',
            'name' => 'required|string|max:50',
            'description' => 'nullable|string|max:255',
            'category_id' => 'required|exists:mst_uom_categories,id',
            'base_uom_id' => 'nullable|exists:mst_uom,id',
            'conversion_factor' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        // Validate that base_uom_id is in the same category
        if ($validatedData['base_uom_id']) {
            $baseUom = Uom::find($validatedData['base_uom_id']);
            if ($baseUom && $baseUom->category_id !== (int)$validatedData['category_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Base UoM must be in the same category.',
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $uom = Uom::create([
                'code' => strtoupper($validatedData['code']),
                'name' => $validatedData['name'],
                'description' => $validatedData['description'] ?? null,
                'category_id' => $validatedData['category_id'],
                'base_uom_id' => $validatedData['base_uom_id'] ?? null,
                'conversion_factor' => $validatedData['conversion_factor'] ?? ($validatedData['base_uom_id'] ? 1 : null),
                'is_active' => $validatedData['is_active'] ?? true,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'UoM created successfully.',
                'uom' => $uom,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create UoM',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $uom = Uom::findOrFail($id);

        $validatedData = $request->validate([
            'code' => 'required|string|max:10|unique:mst_uom,code,' . $id,
            'name' => 'required|string|max:50',
            'description' => 'nullable|string|max:255',
            'category_id' => 'required|exists:mst_uom_categories,id',
            'base_uom_id' => 'nullable|exists:mst_uom,id|different:id',
            'conversion_factor' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        // Validate that base_uom_id is in the same category
        if ($validatedData['base_uom_id']) {
            $baseUom = Uom::find($validatedData['base_uom_id']);
            if ($baseUom && $baseUom->category_id !== (int)$validatedData['category_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Base UoM must be in the same category.',
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $uom->update([
                'code' => strtoupper($validatedData['code']),
                'name' => $validatedData['name'],
                'description' => $validatedData['description'] ?? null,
                'category_id' => $validatedData['category_id'],
                'base_uom_id' => $validatedData['base_uom_id'] ?? null,
                'conversion_factor' => $validatedData['conversion_factor'] ?? ($validatedData['base_uom_id'] ? 1 : null),
                'is_active' => $validatedData['is_active'] ?? true,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'UoM updated successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update UoM',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $uom = Uom::withCount(['products', 'productUoms', 'derivedUoms'])->findOrFail($id);

        // Check if UoM is used by products
        if ($uom->products_count > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete UoM. It is used by {$uom->products_count} product(s) as base UoM.",
            ], 422);
        }

        // Check if UoM is used in product UoM configurations
        if ($uom->product_uoms_count > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete UoM. It is used in {$uom->product_uoms_count} product UoM configuration(s).",
            ], 422);
        }

        // Check if UoM has derived UoMs
        if ($uom->derived_uoms_count > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete UoM. It has {$uom->derived_uoms_count} derived UoM(s).",
            ], 422);
        }

        DB::beginTransaction();
        try {
            $uom->delete();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'UoM deleted successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete UoM.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function toggleStatus($id)
    {
        $uom = Uom::findOrFail($id);

        DB::beginTransaction();
        try {
            $uom->update(['is_active' => !$uom->is_active]);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully.',
                'is_active' => $uom->is_active,
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

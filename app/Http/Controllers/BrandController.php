<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Brand;
use Inertia\Inertia;
use Carbon\Carbon;

class BrandController extends Controller
{
    public function list()
    {
        $brands = Brand::orderByDesc('created_at')->get();

        $data = $brands->map(function ($brand) {
            return [
                'id' => $brand->id,
                'name' => $brand->name,
                'created_at' => Carbon::parse($brand->created_at)->format('d M Y - H:i'),
            ];
        });

        $totalBrands = $brands->count();

        return Inertia::render('Brand/List', [
            'brands' => $data,
            'totalBrands' => $totalBrands,
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
}

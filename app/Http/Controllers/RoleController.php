<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Carbon\Carbon;
use Inertia\Inertia;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = null;

        // Build the query
        $query = Role::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Apply sorting

        // Fetch all roles & permissions
        $permissions = Permission::all();
        $roles = $query->with('permissions')->get(); // Make sure to eager load permissions

        // Transform data to match the required structure
        $data = $roles->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name') // Get permission names as an array
            ];
        });

        
        // Return all data for Inertia rendering
        return Inertia::render('Role/Index', [
            'permissions' => $permissions,
            'roles' => $data,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name', // Ensure permissions exist in the DB
        ],[
            'name.required' => 'The role name is required.',
        ]);

        DB::beginTransaction();
        try {

            $role = Role::create([
                'name' => $validated['name'],
                'guard_name' => 'web', // Set the guard_name to 'web'
            ]);
            
            $permissions = array_values($validated['permissions'] ?? []);
            $role->syncPermissions($permissions);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $validated['name'] . ' role created successfully',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create role.',
                'error' => [$e->getMessage()],
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

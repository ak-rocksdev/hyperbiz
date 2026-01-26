<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionController extends Controller
{
    /**
     * List all permissions with role counts.
     */
    public function index()
    {
        $permissions = Permission::withCount('roles')
            ->get()
            ->map(function ($permission) {
                $parts = explode('.', $permission->name);
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'module' => $parts[0] ?? 'other',
                    'action' => $parts[1] ?? $permission->name,
                    'roles_count' => $permission->roles_count,
                    'roles' => $permission->roles->pluck('name'),
                ];
            });

        return response()->json([
            'success' => true,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Create a new permission.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name|regex:/^[a-z0-9\-]+\.[a-z]+$/',
        ], [
            'name.regex' => 'Permission name must follow format: module.action (e.g., users.view)',
            'name.unique' => 'This permission already exists.',
        ]);

        DB::beginTransaction();
        try {
            $permission = Permission::create([
                'name' => $validated['name'],
                'guard_name' => 'web',
            ]);

            // Clear permission cache
            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Permission created successfully.',
                'permission' => $permission,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create permission.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a permission.
     */
    public function update(Request $request, string $id)
    {
        $permission = Permission::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $id . '|regex:/^[a-z0-9\-]+\.[a-z]+$/',
        ], [
            'name.regex' => 'Permission name must follow format: module.action (e.g., users.view)',
            'name.unique' => 'This permission name is already taken.',
        ]);

        DB::beginTransaction();
        try {
            $permission->update(['name' => $validated['name']]);

            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Permission updated successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update permission.',
            ], 500);
        }
    }

    /**
     * Delete a permission.
     */
    public function destroy(string $id)
    {
        $permission = Permission::findOrFail($id);

        // Check if permission is used by roles
        $rolesUsingPermission = $permission->roles()->count();

        if ($rolesUsingPermission > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete permission. It is used by {$rolesUsingPermission} role(s).",
                'roles_count' => $rolesUsingPermission,
            ], 400);
        }

        $permission->delete();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return response()->json([
            'success' => true,
            'message' => 'Permission deleted successfully.',
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use App\Models\User;
use Carbon\Carbon;
use Inertia\Inertia;

class RoleController extends Controller
{
    /**
     * Display the Access Management page with Users, Roles, and Permissions tabs.
     */
    public function index(Request $request)
    {
        $search = $request->get('search', null);
        $activeTab = $request->get('tab', 'users');

        // ========== ROLES DATA ==========
        // Get user counts per role from pivot table (avoiding Spatie's users() relationship issue)
        $roleCounts = DB::table('model_has_roles')
            ->select('role_id', DB::raw('COUNT(*) as users_count'))
            ->where('model_type', User::class)
            ->groupBy('role_id')
            ->pluck('users_count', 'role_id');

        $roles = Role::with('permissions')
            ->get()
            ->map(function ($role) use ($roleCounts) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'permissions' => $role->permissions->pluck('name')->toArray(),
                    'users_count' => $roleCounts[$role->id] ?? 0,
                    'created_at' => Carbon::parse($role->created_at)->format('d M Y'),
                ];
            });

        // ========== PERMISSIONS DATA ==========
        $permissions = Permission::withCount('roles')->get();

        // Group permissions by module
        $permissionsGrouped = $permissions->groupBy(function ($permission) {
            return explode('.', $permission->name)[0];
        })->map(function ($group) {
            return $group->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'roles_count' => $permission->roles_count,
                ];
            })->values();
        });

        // Permissions list for table display
        $permissionsList = $permissions->map(function ($permission) {
            $parts = explode('.', $permission->name);
            return [
                'id' => $permission->id,
                'name' => $permission->name,
                'module' => $parts[0] ?? 'other',
                'action' => $parts[1] ?? $permission->name,
                'roles_count' => $permission->roles_count,
                'roles' => $permission->roles->pluck('name')->toArray(),
            ];
        });

        // ========== USERS DATA (for Users tab) ==========
        $perPage = $request->get('per_page', 10);
        $roleFilter = $request->get('role', null);
        $statusFilter = $request->get('status', null);

        $usersQuery = User::with('roles');

        if ($search && $activeTab === 'users') {
            $usersQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($roleFilter) {
            $usersQuery->whereHas('roles', function ($q) use ($roleFilter) {
                $q->where('name', $roleFilter);
            });
        }

        if ($statusFilter !== null && $statusFilter !== '') {
            $usersQuery->where('is_active', $statusFilter === 'active' ? 1 : 0);
        }

        $usersQuery->orderByDesc('created_at');
        $usersPaginated = $usersQuery->paginate($perPage);

        $users = $usersPaginated->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_photo_path' => $user->profile_photo_path,
                'profile_photo_url' => $user->profile_photo_url,
                'roles' => $user->roles->pluck('name')->toArray(),
                'is_active' => $user->is_active ?? true,
                'created_at' => Carbon::parse($user->created_at)->format('d M Y H:i'),
                'human_readable_time' => $user->created_at->diffForHumans(),
            ];
        });

        // ========== STATS ==========
        $stats = [
            'total_roles' => Role::count(),
            'total_permissions' => Permission::count(),
            'users_with_roles' => User::whereHas('roles')->count(),
            'total_users' => User::count(),
        ];

        return Inertia::render('AccessManagement/Index', [
            'roles' => $roles,
            'permissions' => $permissionsList,
            'permissionsGrouped' => $permissionsGrouped,
            'users' => $users,
            'pagination' => [
                'total' => $usersPaginated->total(),
                'per_page' => $usersPaginated->perPage(),
                'current_page' => $usersPaginated->currentPage(),
                'last_page' => $usersPaginated->lastPage(),
            ],
            'stats' => $stats,
            'activeTab' => $activeTab,
            'filters' => [
                'search' => $search,
                'role' => $roleFilter,
                'status' => $statusFilter,
            ],
        ]);
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ], [
            'name.required' => 'The role name is required.',
            'name.unique' => 'This role name already exists.',
        ]);

        DB::beginTransaction();
        try {
            $role = Role::create([
                'name' => $validated['name'],
                'guard_name' => 'web',
            ]);

            $permissions = array_values($validated['permissions'] ?? []);
            $role->syncPermissions($permissions);

            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Role created successfully.',
                'role' => $role,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create role.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified role.
     */
    public function show(string $id)
    {
        $role = Role::with('permissions')->withCount('users')->findOrFail($id);

        return response()->json([
            'success' => true,
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->toArray(),
                'users_count' => $role->users_count,
            ],
        ]);
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, string $id)
    {
        $role = Role::findOrFail($id);

        // Prevent renaming superadmin
        if ($role->name === 'superadmin' && $request->name !== 'superadmin') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot rename the superadmin role.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ], [
            'name.required' => 'The role name is required.',
            'name.unique' => 'This role name already exists.',
        ]);

        DB::beginTransaction();
        try {
            $role->update(['name' => $validated['name']]);
            $role->syncPermissions($validated['permissions'] ?? []);

            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Role updated successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update role.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified role.
     */
    public function destroy(string $id)
    {
        $role = Role::findOrFail($id);

        // Prevent deleting superadmin role
        if ($role->name === 'superadmin') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete the superadmin role.',
            ], 403);
        }

        // Check if role has users assigned (query pivot table directly)
        $usersCount = DB::table('model_has_roles')
            ->where('role_id', $role->id)
            ->where('model_type', User::class)
            ->count();
        if ($usersCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete role. {$usersCount} user(s) are assigned to this role.",
            ], 400);
        }

        $role->delete();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully.',
        ]);
    }

    /**
     * Duplicate a role with its permissions.
     */
    public function duplicate(Request $request, string $id)
    {
        $sourceRole = Role::with('permissions')->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
        ], [
            'name.required' => 'The new role name is required.',
            'name.unique' => 'This role name already exists.',
        ]);

        DB::beginTransaction();
        try {
            $newRole = Role::create([
                'name' => $validated['name'],
                'guard_name' => 'web',
            ]);

            $newRole->syncPermissions($sourceRole->permissions->pluck('name')->toArray());

            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Role duplicated successfully.',
                'role' => $newRole,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate role.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk delete roles.
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:roles,id',
        ]);

        // Get roles that have users assigned (query pivot table directly)
        $roleIdsWithUsers = DB::table('model_has_roles')
            ->whereIn('role_id', $validated['ids'])
            ->where('model_type', User::class)
            ->distinct()
            ->pluck('role_id')
            ->toArray();

        $rolesWithUsers = Role::whereIn('id', $roleIdsWithUsers)
            ->pluck('name')
            ->toArray();

        if (count($rolesWithUsers) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete roles with assigned users: ' . implode(', ', $rolesWithUsers),
            ], 400);
        }

        // Check for superadmin in the list
        $hasSuperadmin = Role::whereIn('id', $validated['ids'])
            ->where('name', 'superadmin')
            ->exists();

        if ($hasSuperadmin) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete the superadmin role.',
            ], 403);
        }

        $deletedCount = Role::whereIn('id', $validated['ids'])->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return response()->json([
            'success' => true,
            'message' => "{$deletedCount} role(s) deleted successfully.",
        ]);
    }
}

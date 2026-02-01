<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of users with pagination.
     */
    public function index(Request $request)
    {
        $search = $request->get('search', null);
        $perPage = $request->get('per_page', 10);
        $roleFilter = $request->get('role', null);
        $statusFilter = $request->get('status', null);

        // Get current user's company
        $companyId = auth()->user()->company_id;

        // Build the query - only users from same company, exclude platform admins
        $query = User::with('roles')
            ->where('company_id', $companyId)
            ->where('is_platform_admin', false);

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Apply role filter
        if ($roleFilter) {
            $query->whereHas('roles', function ($q) use ($roleFilter) {
                $q->where('name', $roleFilter);
            });
        }

        // Apply status filter
        if ($statusFilter !== null && $statusFilter !== '') {
            $query->where('is_active', $statusFilter === 'active' ? 1 : 0);
        }

        // Order by latest first
        $query->orderByDesc('created_at');

        // Paginate
        $users = $query->paginate($perPage);

        // Get all roles for filter dropdown
        $roles = Role::orderBy('name')->get();

        // Transform data
        $data = $users->map(function ($user) {
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

        return Inertia::render('User/Index', [
            'users' => $data,
            'roles' => $roles,
            'pagination' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
            ],
            'filters' => [
                'search' => $search,
                'role' => $roleFilter,
                'status' => $statusFilter,
            ],
        ]);
    }

    /**
     * Get user details via API.
     */
    public function detailApi($id)
    {
        $companyId = auth()->user()->company_id;
        $user = User::with('roles')
            ->where('company_id', $companyId)
            ->where('is_platform_admin', false)
            ->findOrFail($id);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_photo_path' => $user->profile_photo_path,
                'profile_photo_url' => $user->profile_photo_url,
                'roles' => $user->roles->pluck('name')->toArray(),
                'is_active' => $user->is_active ?? true,
                'created_at' => Carbon::parse($user->created_at)->format('d M Y H:i'),
                'updated_at' => Carbon::parse($user->updated_at)->format('d M Y H:i'),
            ],
        ]);
    }

    /**
     * Show user detail page.
     */
    public function show($id)
    {
        $companyId = auth()->user()->company_id;
        $user = User::with('roles')
            ->where('company_id', $companyId)
            ->where('is_platform_admin', false)
            ->findOrFail($id);

        return Inertia::render('User/Detail', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_photo_path' => $user->profile_photo_path,
                'profile_photo_url' => $user->profile_photo_url,
                'roles' => $user->roles->pluck('name')->toArray(),
                'is_active' => $user->is_active ?? true,
                'created_at' => Carbon::parse($user->created_at)->format('d M Y H:i'),
                'updated_at' => Carbon::parse($user->updated_at)->format('d M Y H:i'),
            ],
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => 'required|string|exists:roles,name',
        ], [
            'name.required' => 'The user name is required.',
            'email.required' => 'A valid email address is required.',
            'email.unique' => 'This email is already registered.',
            'password.required' => 'The password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            'role.required' => 'Please select a role.',
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'company_id' => auth()->user()->company_id,
                'is_active' => true,
                'is_platform_admin' => false,
                'created_by' => auth()->id(),
            ]);

            $user->syncRoles([$validatedData['role']]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User created successfully.',
                'user' => $user,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create user.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show edit user page.
     */
    public function edit($id)
    {
        $companyId = auth()->user()->company_id;
        $user = User::with('roles')
            ->where('company_id', $companyId)
            ->where('is_platform_admin', false)
            ->findOrFail($id);
        $roles = Role::orderBy('name')->get();

        return Inertia::render('User/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_photo_path' => $user->profile_photo_path,
                'roles' => $user->roles->pluck('name')->toArray(),
                'is_active' => $user->is_active ?? true,
            ],
            'roles' => $roles,
        ]);
    }

    /**
     * Update user via API.
     */
    public function update(Request $request, $id)
    {
        $companyId = auth()->user()->company_id;
        $user = User::where('company_id', $companyId)
            ->where('is_platform_admin', false)
            ->findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $id,
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'role' => 'required|string|exists:roles,name',
        ], [
            'name.required' => 'The user name is required.',
            'email.required' => 'A valid email address is required.',
            'email.unique' => 'This email is already registered.',
            'password.confirmed' => 'Password confirmation does not match.',
            'role.required' => 'Please select a role.',
        ]);

        DB::beginTransaction();
        try {
            $user->name = $validatedData['name'];
            $user->email = $validatedData['email'];
            $user->updated_by = auth()->id();

            if (!empty($validatedData['password'])) {
                $user->password = Hash::make($validatedData['password']);
            }

            $user->save();
            $user->syncRoles([$validatedData['role']]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update user.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle user active status.
     */
    public function toggleStatus($id)
    {
        $companyId = auth()->user()->company_id;
        $user = User::where('company_id', $companyId)
            ->where('is_platform_admin', false)
            ->findOrFail($id);

        // Prevent deactivating yourself
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot deactivate your own account.',
            ], 403);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => $user->is_active ? 'User activated successfully.' : 'User deactivated successfully.',
            'is_active' => $user->is_active,
        ]);
    }

    /**
     * Delete a user.
     */
    public function destroy($id)
    {
        $companyId = auth()->user()->company_id;
        $user = User::where('company_id', $companyId)
            ->where('is_platform_admin', false)
            ->findOrFail($id);

        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account.',
            ], 403);
        }

        // Prevent deleting superadmin
        if ($user->hasRole('superadmin')) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete superadmin account.',
            ], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
        ]);
    }

    /**
     * Get users for API (filtered by company).
     */
    public function indexData(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $users = User::where('company_id', $companyId)
            ->where('is_platform_admin', false)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return response()->json($users);
    }
}

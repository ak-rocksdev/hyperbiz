<?php

namespace App\Http\Controllers;

// use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Inertia\Inertia;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:user-list|user-create|user-edit|user-delete'],['only' => ['index', 'show']]);
        $this->middleware(['permission:user-create'],['only' => ['create', 'store']]);
        $this->middleware(['permission:user-edit'],['only' => ['edit', 'update']]);
        $this->middleware(['permission:user-delete'],['only' => ['destroy']]);
    }
    public function indexData(Request $request)
    {
        $validated = $request->validate([
            'size' => 'nullable|integer|min:1',
            'sortField' => 'nullable|string',
            'sortOrder' => 'nullable|string|in:asc,desc',
            'search' => 'nullable|string',
        ]);

        // Set defaults and extract validated parameters
        $sortField = $validated['sortField'] != "null" && $validated['sortField'] != null ? $validated['sortField'] : 'created_at'; 
        $pageSize = $validated['size'] ?? 10;
        $sortOrder = $validated['sortOrder'] ?? 'desc';
        $search = $validated['search'] ?? null;

        $query = User::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Apply sorting and pagination
        $users = $query->orderBy($sortField, $sortOrder)->paginate($pageSize);

        // Transform data to match the required structure
        $data = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'created_at' => Carbon::parse($user->created_at)->format('d M Y - H:i'),
            ];
        });

        // Return the response
        return response()->json([
            'page' => $users->currentPage(),
            'pageCount' => $users->lastPage(),
            'sortField' => $sortField,
            'sortOrder' => $sortOrder,
            'totalCount' => $users->total(),
            'data' => $data,
        ]);
    }

    public function index(Request $request)
    {
        $search = null;

        // Build the query
        $query = User::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Apply sorting

        // Fetch all users
        $roles = Role::all();
        $users = $query->get();

        // Transform data to match the required structure
        $data = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'profile_photo_path' => $user->profile_photo_path,
                'profile_photo_url' => $user->profile_photo_url,
                'created_at' => Carbon::parse($user->created_at)->format('d M Y - H:i'),
            ];
        });

        // Return all data for Inertia rendering
        return Inertia::render('User/Index', [
            'roles' => $roles,
            'users' => $data,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request, User $user)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|max:255',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name',
        ], [
            'name.required' => 'The user name is required.',
            'email.required' => 'A valid email address is required.',
            'password.required' => 'The user password is required.',
        ]);

        DB::beginTransaction();
        try {
            $validatedData['created_by'] = auth()->id();

            $user = User::create($validatedData);

            $roles = array_values($validatedData['roles']);

            $user->syncRoles($roles);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User created successfully.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create user.',
                'error' => [$e->getMessage()],
            ], 500);
        }
    }
}

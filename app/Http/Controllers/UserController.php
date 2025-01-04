<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

use App\Models\User;
use Inertia\Inertia;

class UserController extends Controller
{
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
            'users' => $data,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }
}

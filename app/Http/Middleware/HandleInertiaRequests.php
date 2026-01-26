<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        // Get user permissions - superadmin gets all permissions
        $permissions = [];
        if ($user) {
            if ($user->hasRole('superadmin')) {
                // Superadmin gets all permissions
                $permissions = \Spatie\Permission\Models\Permission::pluck('name')->toArray();
            } else {
                // Get permissions via roles + direct permissions
                $permissions = $user->getAllPermissions()->pluck('name')->toArray();
            }
        }

        return [
            ...parent::share($request),
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'user.roles' => $user ? $user->roles->pluck('name') : [],
            'user.permissions' => $permissions,
        ];
    }
}

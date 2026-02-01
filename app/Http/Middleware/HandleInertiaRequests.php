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

        // Get user permissions - superadmin/platform admin gets all permissions
        $permissions = [];
        if ($user) {
            if ($user->hasRole('superadmin') || $user->isPlatformAdmin()) {
                // Superadmin and platform admin get all permissions
                $permissions = \Spatie\Permission\Models\Permission::pluck('name')->toArray();
            } else {
                // Get permissions via roles + direct permissions
                $permissions = $user->getAllPermissions()->pluck('name')->toArray();
            }
        }

        // Get company data for the current user
        $companyData = null;
        if ($user && $user->company) {
            $company = $user->company;
            $companyData = [
                'id' => $company->id,
                'name' => $company->name,
                'logo' => $company->logo,
                'website' => $company->website,
                'subscription_status' => $company->subscription_status,
                'subscription_status_label' => $company->subscription_status_label,
                'is_on_trial' => $company->isOnTrial(),
                'is_read_only' => $company->isReadOnly(),
                'trial_days_remaining' => $company->trialDaysRemaining(),
                'trial_ends_at' => $company->trial_ends_at?->toISOString(),
            ];
        }

        return [
            ...parent::share($request),
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'profile_photo_url' => $user->profile_photo_url,
                    'profile_photo_path' => $user->profile_photo_path,
                    'is_platform_admin' => $user->isPlatformAdmin(),
                    'has_company' => $user->hasCompany(),
                    'two_factor_enabled' => ! is_null($user->two_factor_secret),
                    'two_factor_confirmed_at' => $user->two_factor_confirmed_at,
                ] : null,
            ],
            'user' => [
                'roles' => $user ? $user->roles->pluck('name') : [],
                'permissions' => $permissions,
            ],
            'company' => $companyData,
        ];
    }
}

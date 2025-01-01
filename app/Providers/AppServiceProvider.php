<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\LogoutResponse;
use App\Actions\Fortify\CustomLogoutResponse;
use Inertia\Inertia;
use App\Models\Company;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LogoutResponse::class, CustomLogoutResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Inertia::share([
            // Auth data
            'auth' => function () {
                $auth = Auth::check();
                $authMap = $auth->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ];
                });
                return [
                    'user' => Auth::check() ? $authMap : null,
                ];
            },

            // Company data shared globally
            'company' => function () {
                $company = Company::first(); // Adjust query logic as needed
                return $company ? [
                    'name' => $company->name,
                    'logo' => $company->logo,
                    'website' => $company->website,
                ] : null;
            },
        ]);
    }
}

<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\LogoutResponse;
use App\Actions\Fortify\CustomLogoutResponse;
use Inertia\Inertia;
use App\Models\Company;
use App\Traits\LogsSystemChanges;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
        foreach (glob(app_path('Models') . '/*.php') as $modelFile) {
            $modelName = 'App\\Models\\' . basename($modelFile, '.php');

            if (class_exists($modelName) && !is_a($modelName, SystemLog::class, true)) {
                $modelName::booting(function ($model) {
                    $model->useTrait(LogsSystemChanges::class);
                });
            }
        }

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

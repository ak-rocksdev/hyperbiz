<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasCompany
{
    /**
     * Routes that should be excluded from company check.
     */
    protected array $except = [
        'onboarding',
        'onboarding/*',
        'logout',
        'admin',
        'admin/*',
        'user/profile',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Skip for guests
        if (!$user) {
            return $next($request);
        }

        // Platform admins don't need a company
        if ($user->isPlatformAdmin()) {
            return $next($request);
        }

        // Check if route should be excluded
        foreach ($this->except as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        // User must have a company
        if (!$user->company_id) {
            return redirect()->route('onboarding.welcome');
        }

        return $next($request);
    }
}

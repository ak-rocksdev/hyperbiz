<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionStatus
{
    /**
     * Routes that should be excluded from subscription check.
     */
    protected array $except = [
        'onboarding',
        'onboarding/*',
        'logout',
        'platform-admin/*',
        'subscription/*',
        'profile',
        'profile/*',
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

        // Platform admins bypass subscription checks
        if ($user->isPlatformAdmin()) {
            return $next($request);
        }

        // Skip if user has no company yet
        if (!$user->company_id) {
            return $next($request);
        }

        // Check if route should be excluded
        foreach ($this->except as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        $company = $user->company;

        if (!$company) {
            return $next($request);
        }

        // Check subscription status
        $status = $company->subscription_status;

        // Active or trial with valid period - allow all
        if ($status === 'active') {
            return $next($request);
        }

        if ($status === 'trial') {
            // Check if trial has expired
            if ($company->trial_ends_at && $company->trial_ends_at->isPast()) {
                $company->update(['subscription_status' => 'expired']);
                $status = 'expired';
            } else {
                return $next($request);
            }
        }

        // Expired or suspended - read-only mode
        if (in_array($status, ['expired', 'suspended'])) {
            // Allow only GET requests (read-only)
            if ($request->isMethod('GET')) {
                return $next($request);
            }

            // Block write operations
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'subscription_expired',
                    'message' => 'Your subscription has expired. Please upgrade to continue.',
                ], 403);
            }

            return Inertia::render('Subscription/UpgradeRequired', [
                'status' => $status,
                'message' => 'Your subscription has expired. Please upgrade to continue creating and editing data.',
            ])->toResponse($request)->setStatusCode(403);
        }

        // Cancelled - redirect to cancelled page
        if ($status === 'cancelled') {
            return redirect()->route('subscription.cancelled');
        }

        return $next($request);
    }
}

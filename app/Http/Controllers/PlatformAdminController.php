<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use App\Models\SalesOrder;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class PlatformAdminController extends Controller
{
    /**
     * Ensure only platform admins can access these methods.
     */
    private function ensurePlatformAdmin(): void
    {
        if (!auth()->user()?->isPlatformAdmin()) {
            abort(403, 'Access denied. Platform admin only.');
        }
    }

    /**
     * Platform Admin Dashboard
     */
    public function dashboard()
    {
        $this->ensurePlatformAdmin();

        $stats = [
            'total_companies' => Company::count(),
            'active_companies' => Company::where('subscription_status', 'active')->count(),
            'trial_companies' => Company::where('subscription_status', 'trial')->count(),
            'expired_companies' => Company::where('subscription_status', 'expired')->count(),
            'total_users' => User::where('is_platform_admin', false)->count(),
            'total_platform_admins' => User::where('is_platform_admin', true)->count(),
        ];

        // Get recent companies
        $recentCompanies = Company::orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn($company) => [
                'id' => $company->id,
                'name' => $company->name,
                'subscription_status' => $company->subscription_status,
                'subscription_status_label' => $company->subscription_status_label,
                'users_count' => User::where('company_id', $company->id)->count(),
                'trial_ends_at' => $company->trial_ends_at?->format('d M Y'),
                'created_at' => $company->created_at->format('d M Y'),
            ]);

        // Companies by subscription status for chart
        $subscriptionChart = [
            'labels' => ['Trial', 'Active', 'Past Due', 'Expired', 'Cancelled'],
            'data' => [
                Company::where('subscription_status', 'trial')->count(),
                Company::where('subscription_status', 'active')->count(),
                Company::where('subscription_status', 'past_due')->count(),
                Company::where('subscription_status', 'expired')->count(),
                Company::where('subscription_status', 'cancelled')->count(),
            ],
            'colors' => ['#3B82F6', '#22C55E', '#F59E0B', '#EF4444', '#6B7280'],
        ];

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentCompanies' => $recentCompanies,
            'subscriptionChart' => $subscriptionChart,
        ]);
    }

    /**
     * List all companies
     */
    public function companies(Request $request)
    {
        $this->ensurePlatformAdmin();

        $query = Company::query();

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by subscription status
        if ($status = $request->input('status')) {
            $query->where('subscription_status', $status);
        }

        $companies = $query->orderByDesc('created_at')
            ->paginate(15)
            ->through(fn($company) => [
                'id' => $company->id,
                'name' => $company->name,
                'email' => $company->email,
                'phone' => $company->phone,
                'subscription_status' => $company->subscription_status,
                'subscription_status_label' => $company->subscription_status_label,
                'users_count' => User::where('company_id', $company->id)->count(),
                'trial_ends_at' => $company->trial_ends_at?->format('d M Y'),
                'subscription_ends_at' => $company->subscription_ends_at?->format('d M Y'),
                'created_at' => $company->created_at->format('d M Y'),
            ]);

        return Inertia::render('Admin/Companies/Index', [
            'companies' => $companies,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Show company detail
     */
    public function companyDetail(Company $company)
    {
        $this->ensurePlatformAdmin();

        $company->load('users');

        $companyData = [
            'id' => $company->id,
            'name' => $company->name,
            'email' => $company->email,
            'phone' => $company->phone,
            'address' => $company->address,
            'website' => $company->website,
            'logo' => $company->logo,
            'subscription_status' => $company->subscription_status,
            'subscription_status_label' => $company->subscription_status_label,
            'trial_ends_at' => $company->trial_ends_at?->format('d M Y H:i'),
            'subscription_starts_at' => $company->subscription_starts_at?->format('d M Y H:i'),
            'subscription_ends_at' => $company->subscription_ends_at?->format('d M Y H:i'),
            'created_at' => $company->created_at->format('d M Y H:i'),
            'updated_at' => $company->updated_at->format('d M Y H:i'),
        ];

        $users = $company->users->map(fn($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_active' => $user->is_active,
            'roles' => $user->roles->pluck('name'),
            'created_at' => $user->created_at->format('d M Y'),
        ]);

        // Get company statistics (bypass global scope)
        $stats = [
            'users_count' => $company->users->count(),
            'products_count' => \App\Models\Product::withoutGlobalScope('company')->where('company_id', $company->id)->count(),
            'customers_count' => \App\Models\Customer::withoutGlobalScope('company')->where('company_id', $company->id)->count(),
            'sales_orders_count' => SalesOrder::withoutGlobalScope('company')->where('company_id', $company->id)->count(),
            'purchase_orders_count' => PurchaseOrder::withoutGlobalScope('company')->where('company_id', $company->id)->count(),
        ];

        return Inertia::render('Admin/Companies/Detail', [
            'company' => $companyData,
            'users' => $users,
            'stats' => $stats,
        ]);
    }

    /**
     * Update company subscription status
     */
    public function updateCompanySubscription(Request $request, Company $company)
    {
        $this->ensurePlatformAdmin();

        $validated = $request->validate([
            'subscription_status' => 'required|in:trial,active,past_due,expired,cancelled',
            'trial_ends_at' => 'nullable|date',
            'subscription_ends_at' => 'nullable|date',
        ]);

        $company->update($validated);

        return back()->with('success', 'Company subscription updated successfully.');
    }
}

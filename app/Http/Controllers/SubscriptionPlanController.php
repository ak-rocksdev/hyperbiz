<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubscriptionPlanController extends Controller
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
     * Display a listing of subscription plans.
     */
    public function index(Request $request)
    {
        $this->ensurePlatformAdmin();

        $query = SubscriptionPlan::query();

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->input('status') !== '') {
            $isActive = $request->input('status') === 'active';
            $query->where('is_active', $isActive);
        }

        $plans = $query->ordered()
            ->withCount('companies')
            ->get()
            ->map(fn($plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'description' => $plan->description,
                'price_monthly' => $plan->price_monthly,
                'price_yearly' => $plan->price_yearly,
                'formatted_price_monthly' => number_format($plan->price_monthly, 2),
                'formatted_price_yearly' => number_format($plan->price_yearly, 2),
                'yearly_discount' => $plan->yearly_discount_percentage,
                'max_users' => $plan->max_users,
                'max_products' => $plan->max_products,
                'max_customers' => $plan->max_customers,
                'max_monthly_orders' => $plan->max_monthly_orders,
                'features' => $plan->features,
                'is_active' => $plan->is_active,
                'sort_order' => $plan->sort_order,
                'companies_count' => $plan->companies_count,
                'created_at' => $plan->created_at->format('d M Y'),
            ]);

        $stats = [
            'total' => SubscriptionPlan::count(),
            'active' => SubscriptionPlan::where('is_active', true)->count(),
            'inactive' => SubscriptionPlan::where('is_active', false)->count(),
        ];

        return Inertia::render('Admin/Plans/Index', [
            'plans' => $plans,
            'filters' => $request->only(['search', 'status']),
            'stats' => $stats,
        ]);
    }

    /**
     * Store a newly created plan.
     */
    public function store(Request $request)
    {
        $this->ensurePlatformAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100|unique:subscription_plans,slug',
            'description' => 'nullable|string',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'required|numeric|min:0',
            'max_users' => 'required|integer|min:0',
            'max_products' => 'required|integer|min:0',
            'max_customers' => 'required|integer|min:0',
            'max_monthly_orders' => 'required|integer|min:0',
            'features' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        // Auto-generate sort_order (max + 1, or 1 if no records)
        $maxSortOrder = SubscriptionPlan::max('sort_order') ?? 0;
        $validated['sort_order'] = $maxSortOrder + 1;

        $plan = SubscriptionPlan::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Subscription plan created successfully.',
            'plan' => $plan,
        ]);
    }

    /**
     * Update the specified plan.
     */
    public function update(Request $request, SubscriptionPlan $plan)
    {
        $this->ensurePlatformAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100|unique:subscription_plans,slug,' . $plan->id,
            'description' => 'nullable|string',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'required|numeric|min:0',
            'max_users' => 'required|integer|min:0',
            'max_products' => 'required|integer|min:0',
            'max_customers' => 'required|integer|min:0',
            'max_monthly_orders' => 'required|integer|min:0',
            'features' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        // Don't update sort_order from form - use drag-drop reordering instead
        $plan->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Subscription plan updated successfully.',
            'plan' => $plan,
        ]);
    }

    /**
     * Toggle plan active status.
     */
    public function toggleStatus(SubscriptionPlan $plan)
    {
        $this->ensurePlatformAdmin();

        $plan->is_active = !$plan->is_active;
        $plan->save();

        return response()->json([
            'success' => true,
            'message' => $plan->is_active ? 'Plan activated successfully.' : 'Plan deactivated successfully.',
            'is_active' => $plan->is_active,
        ]);
    }

    /**
     * Remove the specified plan.
     */
    public function destroy(SubscriptionPlan $plan)
    {
        $this->ensurePlatformAdmin();

        // Check if any companies are using this plan
        if ($plan->companies()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete plan with active subscriptions. Please reassign companies first.',
            ], 422);
        }

        $plan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Subscription plan deleted successfully.',
        ]);
    }

    /**
     * Update sort order of plans.
     */
    public function updateOrder(Request $request)
    {
        $this->ensurePlatformAdmin();

        $validated = $request->validate([
            'plans' => 'required|array',
            'plans.*.id' => 'required|exists:subscription_plans,id',
            'plans.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['plans'] as $planData) {
            SubscriptionPlan::where('id', $planData['id'])
                ->update(['sort_order' => $planData['sort_order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Plan order updated successfully.',
        ]);
    }
}

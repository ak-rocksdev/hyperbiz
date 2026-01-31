<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ChartOfAccountController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:finance.chart_of_accounts.view', only: ['index', 'getForSelect']),
            new Middleware('permission:finance.chart_of_accounts.manage', only: ['store', 'update', 'destroy', 'toggleStatus']),
        ];
    }
    /**
     * Display the chart of accounts list.
     */
    public function index(Request $request)
    {
        $query = ChartOfAccount::query()
            ->with(['parent:id,account_code,account_name'])
            ->orderBy('account_code');

        // Filter by account type
        if ($request->filled('type')) {
            $query->where('account_type', $request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Filter by level
        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('account_code', 'like', "%{$search}%")
                    ->orWhere('account_name', 'like', "%{$search}%");
            });
        }

        $accounts = $query->get();

        // Build tree structure
        $accountTree = $this->buildAccountTree($accounts);

        // Get stats
        $stats = [
            'total' => ChartOfAccount::count(),
            'active' => ChartOfAccount::where('is_active', true)->count(),
            'postable' => ChartOfAccount::postable()->count(),
            'by_type' => ChartOfAccount::selectRaw('account_type, count(*) as count')
                ->groupBy('account_type')
                ->pluck('count', 'account_type'),
        ];

        return Inertia::render('Finance/ChartOfAccounts/Index', [
            'accounts' => $accounts,
            'accountTree' => $accountTree,
            'stats' => $stats,
            'filters' => $request->only(['type', 'status', 'level', 'search']),
            'accountTypes' => ChartOfAccount::ACCOUNT_TYPES,
            'accountTypeColors' => ChartOfAccount::ACCOUNT_TYPE_COLORS,
        ]);
    }

    /**
     * Store a new account.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_code' => ['required', 'string', 'max:20', 'unique:fin_chart_of_accounts,account_code'],
            'account_name' => ['required', 'string', 'max:100'],
            'account_type' => ['required', Rule::in(array_keys(ChartOfAccount::ACCOUNT_TYPES))],
            'normal_balance' => ['required', Rule::in(['debit', 'credit'])],
            'parent_id' => ['nullable', 'exists:fin_chart_of_accounts,id'],
            'is_header' => ['boolean'],
            'is_bank_account' => ['boolean'],
            'description' => ['nullable', 'string'],
            'currency_code' => ['string', 'size:3'],
            'opening_balance' => ['nullable', 'numeric'],
            'opening_balance_date' => ['nullable', 'date'],
        ]);

        // Determine level based on parent
        $level = 1;
        if ($validated['parent_id']) {
            $parent = ChartOfAccount::find($validated['parent_id']);
            $level = $parent ? $parent->level + 1 : 1;
        }

        $account = ChartOfAccount::create([
            ...$validated,
            'level' => $level,
            'is_active' => true,
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Account created successfully.',
            'account' => $account,
        ]);
    }

    /**
     * Update an account.
     */
    public function update(Request $request, ChartOfAccount $account)
    {
        if ($account->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'System accounts cannot be modified.',
            ], 403);
        }

        $validated = $request->validate([
            'account_code' => ['required', 'string', 'max:20', Rule::unique('fin_chart_of_accounts', 'account_code')->ignore($account->id)],
            'account_name' => ['required', 'string', 'max:100'],
            'account_type' => ['required', Rule::in(array_keys(ChartOfAccount::ACCOUNT_TYPES))],
            'normal_balance' => ['required', Rule::in(['debit', 'credit'])],
            'parent_id' => ['nullable', 'exists:fin_chart_of_accounts,id'],
            'is_header' => ['boolean'],
            'is_bank_account' => ['boolean'],
            'description' => ['nullable', 'string'],
            'currency_code' => ['string', 'size:3'],
            'opening_balance' => ['nullable', 'numeric'],
            'opening_balance_date' => ['nullable', 'date'],
        ]);

        // Prevent circular reference
        if ($validated['parent_id'] == $account->id) {
            return response()->json([
                'success' => false,
                'message' => 'An account cannot be its own parent.',
            ], 422);
        }

        // Determine level based on parent
        $level = 1;
        if ($validated['parent_id']) {
            $parent = ChartOfAccount::find($validated['parent_id']);
            $level = $parent ? $parent->level + 1 : 1;
        }

        $account->update([
            ...$validated,
            'level' => $level,
            'updated_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Account updated successfully.',
            'account' => $account->fresh(),
        ]);
    }

    /**
     * Delete an account.
     */
    public function destroy(ChartOfAccount $account)
    {
        if (!$account->canDelete()) {
            return response()->json([
                'success' => false,
                'message' => $account->is_system
                    ? 'System accounts cannot be deleted.'
                    : 'Account has child accounts and cannot be deleted.',
            ], 403);
        }

        $account->delete();

        return response()->json([
            'success' => true,
            'message' => 'Account deleted successfully.',
        ]);
    }

    /**
     * Toggle account status.
     */
    public function toggleStatus(ChartOfAccount $account)
    {
        if ($account->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'System accounts cannot be deactivated.',
            ], 403);
        }

        $account->update([
            'is_active' => !$account->is_active,
            'updated_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $account->is_active
                ? 'Account activated successfully.'
                : 'Account deactivated successfully.',
            'is_active' => $account->is_active,
        ]);
    }

    /**
     * Get accounts for dropdown (API).
     */
    public function getForSelect(Request $request)
    {
        $query = ChartOfAccount::active()->postable()->orderBy('account_code');

        if ($request->filled('type')) {
            $query->where('account_type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('account_code', 'like', "%{$search}%")
                    ->orWhere('account_name', 'like', "%{$search}%");
            });
        }

        $accounts = $query->limit(50)->get()->map(fn($account) => [
            'id' => $account->id,
            'code' => $account->account_code,
            'name' => $account->account_name,
            'full_name' => $account->full_name,
            'type' => $account->account_type,
            'type_label' => $account->account_type_label,
            'normal_balance' => $account->normal_balance,
        ]);

        return response()->json(['accounts' => $accounts]);
    }

    /**
     * Build tree structure from flat account list.
     */
    private function buildAccountTree($accounts, $parentId = null): array
    {
        $tree = [];

        foreach ($accounts as $account) {
            if ($account->parent_id === $parentId) {
                $children = $this->buildAccountTree($accounts, $account->id);
                $accountData = $account->toArray();
                $accountData['children'] = $children;
                $tree[] = $accountData;
            }
        }

        return $tree;
    }
}

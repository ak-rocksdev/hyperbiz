<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ExpenseCategoryController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:finance.expenses.view', only: ['index', 'getForSelect']),
            new Middleware('permission:finance.expenses.create', only: ['store']),
            new Middleware('permission:finance.expenses.edit', only: ['update', 'toggleStatus']),
            new Middleware('permission:finance.expenses.delete', only: ['destroy']),
        ];
    }
    /**
     * Display expense categories page.
     */
    public function index(Request $request)
    {
        // Build query
        $query = ExpenseCategory::with(['parent', 'defaultAccount', 'children']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Only root categories with children for tree view
        if (!$request->filled('search') && !$request->filled('flat')) {
            $categories = ExpenseCategory::buildTree();
        } else {
            $categories = $query->orderBy('code')->get()->map(function ($category) {
                return [
                    'id' => $category->id,
                    'code' => $category->code,
                    'name' => $category->name,
                    'full_path' => $category->full_path,
                    'parent_name' => $category->parent?->name,
                    'default_account' => $category->defaultAccount ? [
                        'id' => $category->defaultAccount->id,
                        'code' => $category->defaultAccount->account_code,
                        'name' => $category->defaultAccount->account_name,
                    ] : null,
                    'is_active' => $category->is_active,
                    'can_delete' => $category->canDelete(),
                ];
            })->toArray();
        }

        // Get expense accounts for dropdown
        $accounts = ChartOfAccount::active()
            ->postable()
            ->where('account_type', 'expense')
            ->orderBy('account_code')
            ->get()
            ->map(function ($account) {
                return [
                    'value' => $account->id,
                    'label' => $account->account_code . ' - ' . $account->account_name,
                ];
            });

        // Get parent categories for dropdown (root categories only)
        $parentCategories = ExpenseCategory::root()
            ->active()
            ->orderBy('code')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'code' => $category->code,
                    'name' => $category->name,
                    'level' => 0,
                ];
            });

        return Inertia::render('Finance/ExpenseCategories/Index', [
            'categories' => $categories,
            'accounts' => $accounts,
            'parentCategories' => $parentCategories,
            'filters' => $request->only(['search', 'status']),
            'stats' => [
                'total' => ExpenseCategory::count(),
                'active' => ExpenseCategory::active()->count(),
                'inactive' => ExpenseCategory::where('is_active', false)->count(),
                'root' => ExpenseCategory::root()->count(),
            ],
        ]);
    }

    /**
     * Store a new expense category.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:mst_expense_categories,code'],
            'name' => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable', 'exists:mst_expense_categories,id'],
            'default_account_id' => ['nullable', 'exists:fin_chart_of_accounts,id'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $category = ExpenseCategory::create([
            ...$validated,
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Expense category created successfully.',
            'category' => $category,
        ]);
    }

    /**
     * Update an expense category.
     */
    public function update(Request $request, ExpenseCategory $category)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:mst_expense_categories,code,' . $category->id],
            'name' => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable', 'exists:mst_expense_categories,id'],
            'default_account_id' => ['nullable', 'exists:fin_chart_of_accounts,id'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        // Prevent setting parent to self or own children
        if ($validated['parent_id']) {
            if ($validated['parent_id'] == $category->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'A category cannot be its own parent.',
                ], 422);
            }

            // Check if parent is a child of this category
            $childIds = $category->allChildren->pluck('id')->toArray();
            if (in_array($validated['parent_id'], $childIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot set a child category as parent.',
                ], 422);
            }
        }

        $category->update([
            ...$validated,
            'updated_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Expense category updated successfully.',
            'category' => $category->fresh(),
        ]);
    }

    /**
     * Delete an expense category.
     */
    public function destroy(ExpenseCategory $category)
    {
        if (!$category->canDelete()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete this category. It has child categories or expenses.',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Expense category deleted successfully.',
        ]);
    }

    /**
     * Toggle category status.
     */
    public function toggleStatus(ExpenseCategory $category)
    {
        $category->update([
            'is_active' => !$category->is_active,
            'updated_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $category->is_active ? 'Category activated.' : 'Category deactivated.',
            'is_active' => $category->is_active,
        ]);
    }

    /**
     * Get categories for select dropdown.
     */
    public function getForSelect()
    {
        $categories = ExpenseCategory::active()
            ->orderBy('code')
            ->get()
            ->map(function ($category) {
                return [
                    'value' => $category->id,
                    'label' => $category->code . ' - ' . $category->name,
                    'default_account_id' => $category->default_account_id,
                ];
            });

        return response()->json([
            'success' => true,
            'categories' => $categories,
        ]);
    }
}

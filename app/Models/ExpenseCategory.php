<?php

namespace App\Models;

use App\Traits\LogsSystemChanges;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseCategory extends Model
{
    use HasFactory, LogsSystemChanges;

    protected $table = 'mst_expense_categories';

    protected $fillable = [
        'code',
        'name',
        'parent_id',
        'default_account_id',
        'description',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Parent category relationship.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'parent_id');
    }

    /**
     * Child categories relationship.
     */
    public function children(): HasMany
    {
        return $this->hasMany(ExpenseCategory::class, 'parent_id');
    }

    /**
     * Recursive children relationship.
     */
    public function allChildren(): HasMany
    {
        return $this->children()->with('allChildren');
    }

    /**
     * Default GL account relationship.
     */
    public function defaultAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'default_account_id');
    }

    /**
     * Expenses in this category.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'category_id');
    }

    /**
     * Creator relationship.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Updater relationship.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope for active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for root categories (no parent).
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Get full category path.
     */
    public function getFullPathAttribute(): string
    {
        $path = [$this->name];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }

        return implode(' > ', $path);
    }

    /**
     * Check if category can be deleted.
     */
    public function canDelete(): bool
    {
        // Cannot delete if has children
        if ($this->children()->count() > 0) {
            return false;
        }

        // Cannot delete if has expenses
        if ($this->expenses()->count() > 0) {
            return false;
        }

        return true;
    }

    /**
     * Build tree structure for dropdown/display.
     */
    public static function buildTree(?int $parentId = null, int $level = 0): array
    {
        $categories = self::where('parent_id', $parentId)
            ->orderBy('code')
            ->get();

        $tree = [];

        foreach ($categories as $category) {
            $tree[] = [
                'id' => $category->id,
                'code' => $category->code,
                'name' => $category->name,
                'full_name' => str_repeat('— ', $level) . $category->name,
                'level' => $level,
                'is_active' => $category->is_active,
                'default_account_id' => $category->default_account_id,
                'children' => self::buildTree($category->id, $level + 1),
            ];
        }

        return $tree;
    }

    /**
     * Get flat list for dropdown.
     */
    public static function getForSelect(): array
    {
        $flatList = [];
        self::flattenTree(self::buildTree(), $flatList);
        return $flatList;
    }

    /**
     * Flatten tree to list.
     */
    private static function flattenTree(array $tree, array &$flatList): void
    {
        foreach ($tree as $item) {
            $flatList[] = [
                'id' => $item['id'],
                'code' => $item['code'],
                'name' => $item['full_name'],
                'is_active' => $item['is_active'],
            ];

            if (!empty($item['children'])) {
                self::flattenTree($item['children'], $flatList);
            }
        }
    }
}

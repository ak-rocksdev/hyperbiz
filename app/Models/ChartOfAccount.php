<?php

namespace App\Models;

use App\Traits\LogsSystemChanges;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    use HasFactory, LogsSystemChanges, BelongsToCompany;

    protected $table = 'fin_chart_of_accounts';

    protected $fillable = [
        'account_code',
        'account_name',
        'account_type',
        'normal_balance',
        'parent_id',
        'level',
        'is_header',
        'is_bank_account',
        'is_system',
        'is_active',
        'description',
        'currency_code',
        'opening_balance',
        'opening_balance_date',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'level' => 'integer',
        'is_header' => 'boolean',
        'is_bank_account' => 'boolean',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'opening_balance' => 'decimal:2',
        'opening_balance_date' => 'date',
    ];

    /**
     * Account type labels
     */
    public const ACCOUNT_TYPES = [
        'asset' => 'Asset',
        'liability' => 'Liability',
        'equity' => 'Equity',
        'revenue' => 'Revenue',
        'cogs' => 'Cost of Goods Sold',
        'expense' => 'Expense',
        'other_income' => 'Other Income',
        'other_expense' => 'Other Expense',
    ];

    /**
     * Account type colors for UI
     */
    public const ACCOUNT_TYPE_COLORS = [
        'asset' => 'primary',
        'liability' => 'danger',
        'equity' => 'info',
        'revenue' => 'success',
        'cogs' => 'warning',
        'expense' => 'danger',
        'other_income' => 'success',
        'other_expense' => 'danger',
    ];

    // Relationships

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_id')->orderBy('account_code');
    }

    public function allChildren(): HasMany
    {
        return $this->children()->with('allChildren');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePostable($query)
    {
        return $query->where('is_header', false);
    }

    public function scopeHeaders($query)
    {
        return $query->where('is_header', true);
    }

    public function scopeRootAccounts($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('account_type', $type);
    }

    public function scopeBankAccounts($query)
    {
        return $query->where('is_bank_account', true);
    }

    // Accessors

    public function getAccountTypeLabelAttribute(): string
    {
        return self::ACCOUNT_TYPES[$this->account_type] ?? $this->account_type;
    }

    public function getAccountTypeColorAttribute(): string
    {
        return self::ACCOUNT_TYPE_COLORS[$this->account_type] ?? 'secondary';
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->account_code} - {$this->account_name}";
    }

    public function getIsDebitNormalAttribute(): bool
    {
        return $this->normal_balance === 'debit';
    }

    public function getIsCreditNormalAttribute(): bool
    {
        return $this->normal_balance === 'credit';
    }

    // Methods

    /**
     * Check if account can be deleted
     */
    public function canDelete(): bool
    {
        if ($this->is_system) {
            return false;
        }

        // Check if has children
        if ($this->children()->exists()) {
            return false;
        }

        // TODO: Check if has journal entries when journal module is implemented

        return true;
    }

    /**
     * Get all ancestor accounts
     */
    public function getAncestors(): array
    {
        $ancestors = [];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($ancestors, $parent);
            $parent = $parent->parent;
        }

        return $ancestors;
    }

    /**
     * Get tree path as string
     */
    public function getTreePath(): string
    {
        $ancestors = $this->getAncestors();
        $path = array_map(fn($a) => $a->account_name, $ancestors);
        $path[] = $this->account_name;

        return implode(' > ', $path);
    }

    /**
     * Get flat list of all descendants
     */
    public function getAllDescendants(): \Illuminate\Support\Collection
    {
        $descendants = collect();

        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getAllDescendants());
        }

        return $descendants;
    }

    /**
     * Build tree structure from flat collection
     */
    public static function buildTree(?int $parentId = null): \Illuminate\Support\Collection
    {
        return static::where('parent_id', $parentId)
            ->orderBy('account_code')
            ->get()
            ->map(function ($account) {
                $account->children_tree = static::buildTree($account->id);
                return $account;
            });
    }
}

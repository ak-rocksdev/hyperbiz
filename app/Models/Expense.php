<?php

namespace App\Models;

use App\Traits\LogsSystemChanges;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Expense extends Model
{
    use HasFactory, LogsSystemChanges;

    protected $table = 'fin_expenses';

    protected $fillable = [
        'expense_number',
        'expense_date',
        'category_id',
        'account_id',
        'paid_from_account_id',
        'supplier_id',
        'payee_name',
        'currency_code',
        'exchange_rate',
        'amount',
        'amount_in_base',
        'tax_amount',
        'total_amount',
        'payment_status',
        'amount_paid',
        'payment_method',
        'reference_number',
        'description',
        'notes',
        'is_recurring',
        'recurring_frequency',
        'journal_entry_id',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'exchange_rate' => 'decimal:6',
        'amount' => 'decimal:2',
        'amount_in_base' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'is_recurring' => 'boolean',
        'approved_at' => 'datetime',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_APPROVED = 'approved';
    const STATUS_POSTED = 'posted';
    const STATUS_CANCELLED = 'cancelled';

    // Payment status constants
    const PAYMENT_UNPAID = 'unpaid';
    const PAYMENT_PARTIAL = 'partial';
    const PAYMENT_PAID = 'paid';

    // Payment method constants
    const PAYMENT_METHOD_CASH = 'cash';
    const PAYMENT_METHOD_BANK_TRANSFER = 'bank_transfer';
    const PAYMENT_METHOD_CREDIT_CARD = 'credit_card';
    const PAYMENT_METHOD_CHECK = 'check';
    const PAYMENT_METHOD_OTHER = 'other';

    /**
     * Category relationship.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    /**
     * Expense GL account relationship.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    /**
     * Paid from account relationship.
     */
    public function paidFromAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'paid_from_account_id');
    }

    /**
     * Supplier/Vendor relationship.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'supplier_id');
    }

    /**
     * Attachments relationship.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(ExpenseAttachment::class, 'expense_id');
    }

    /**
     * Creator relationship.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Approver relationship.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Journal entry relationship.
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    /**
     * Generate next expense number.
     */
    public static function generateNumber(): string
    {
        $year = date('Y');
        $prefix = "EXP-{$year}-";

        $lastExpense = self::where('expense_number', 'like', $prefix . '%')
            ->orderBy('expense_number', 'desc')
            ->first();

        if ($lastExpense) {
            $lastNumber = (int) substr($lastExpense->expense_number, -5);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate and set amounts using bcmath for precision.
     */
    public function calculateAmounts(): void
    {
        $this->amount_in_base = bcmul((string) $this->amount, (string) $this->exchange_rate, 2);
        $this->total_amount = bcadd((string) $this->amount, (string) $this->tax_amount, 2);
    }

    /**
     * Approve expense.
     */
    public function approve(int $userId): bool
    {
        if ($this->status !== self::STATUS_DRAFT) {
            return false;
        }

        $this->status = self::STATUS_APPROVED;
        $this->approved_by = $userId;
        $this->approved_at = now();
        return $this->save();
    }

    /**
     * Post expense (creates journal entry).
     */
    public function post(): bool
    {
        if ($this->status !== self::STATUS_APPROVED) {
            return false;
        }

        $this->status = self::STATUS_POSTED;
        // Journal entry creation will be handled by JournalEntryService
        return $this->save();
    }

    /**
     * Cancel expense.
     */
    public function cancel(): bool
    {
        if ($this->status === self::STATUS_POSTED) {
            return false; // Cannot cancel posted expense
        }

        $this->status = self::STATUS_CANCELLED;
        return $this->save();
    }

    /**
     * Record payment using bcmath for precision.
     */
    public function recordPayment(string|float $amount): void
    {
        $this->amount_paid = bcadd((string) $this->amount_paid, (string) $amount, 2);

        // Use bccomp for precise comparison (returns -1, 0, or 1)
        $comparison = bccomp((string) $this->amount_paid, (string) $this->total_amount, 2);
        if ($comparison >= 0) {
            $this->payment_status = self::PAYMENT_PAID;
        } elseif (bccomp((string) $this->amount_paid, '0', 2) > 0) {
            $this->payment_status = self::PAYMENT_PARTIAL;
        }

        $this->save();
    }

    /**
     * Get balance due using bcmath for precision.
     */
    public function getBalanceDueAttribute(): string
    {
        return bcsub((string) $this->total_amount, (string) $this->amount_paid, 2);
    }

    /**
     * Status label accessor.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_POSTED => 'Posted',
            self::STATUS_CANCELLED => 'Cancelled',
            default => 'Unknown',
        };
    }

    /**
     * Status color accessor.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'gray',
            self::STATUS_APPROVED => 'primary',
            self::STATUS_POSTED => 'success',
            self::STATUS_CANCELLED => 'danger',
            default => 'gray',
        };
    }

    /**
     * Payment status label accessor.
     */
    public function getPaymentStatusLabelAttribute(): string
    {
        return match ($this->payment_status) {
            self::PAYMENT_UNPAID => 'Unpaid',
            self::PAYMENT_PARTIAL => 'Partial',
            self::PAYMENT_PAID => 'Paid',
            default => 'Unknown',
        };
    }

    /**
     * Payment status color accessor.
     */
    public function getPaymentStatusColorAttribute(): string
    {
        return match ($this->payment_status) {
            self::PAYMENT_UNPAID => 'danger',
            self::PAYMENT_PARTIAL => 'warning',
            self::PAYMENT_PAID => 'success',
            default => 'gray',
        };
    }

    /**
     * Scope for draft expenses.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope for approved expenses.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope for posted expenses.
     */
    public function scopePosted($query)
    {
        return $query->where('status', self::STATUS_POSTED);
    }

    /**
     * Scope by date range.
     */
    public function scopeDateBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('expense_date', [$startDate, $endDate]);
    }

    /**
     * Scope by category.
     */
    public function scopeInCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Check if expense can be edited.
     */
    public function canEdit(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_APPROVED]);
    }

    /**
     * Check if expense can be deleted.
     */
    public function canDelete(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if expense can be approved.
     */
    public function canApprove(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if expense can be posted.
     */
    public function canPost(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
}

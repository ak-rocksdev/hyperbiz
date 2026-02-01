<?php

namespace App\Models;

use App\Traits\LogsSystemChanges;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class JournalEntry extends Model
{
    use LogsSystemChanges, BelongsToCompany;

    protected $table = 'fin_journal_entries';

    protected $fillable = [
        'entry_number',
        'entry_date',
        'fiscal_period_id',
        'entry_type',
        'reference_type',
        'reference_id',
        'memo',
        'currency_code',
        'exchange_rate',
        'total_debit',
        'total_credit',
        'status',
        'reversed_by_id',
        'reverses_id',
        'created_by',
        'posted_by',
        'posted_at',
        'voided_by',
        'voided_at',
        'void_reason',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'exchange_rate' => 'decimal:6',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
        'posted_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    /**
     * Entry type labels
     */
    public const ENTRY_TYPES = [
        'manual' => 'Manual Entry',
        'auto_sales' => 'Auto - Sales',
        'auto_purchase' => 'Auto - Purchase',
        'auto_payment' => 'Auto - Payment',
        'auto_expense' => 'Auto - Expense',
        'closing' => 'Closing Entry',
        'opening' => 'Opening Entry',
        'adjustment' => 'Adjustment',
    ];

    /**
     * Status labels
     */
    public const STATUSES = [
        'draft' => 'Draft',
        'posted' => 'Posted',
        'voided' => 'Voided',
    ];

    /**
     * Status colors for UI
     */
    public const STATUS_COLORS = [
        'draft' => 'light',
        'posted' => 'success',
        'voided' => 'danger',
    ];

    // Relationships

    public function fiscalPeriod(): BelongsTo
    {
        return $this->belongsTo(FiscalPeriod::class, 'fiscal_period_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'journal_entry_id')->orderBy('line_number');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reversed_by_id');
    }

    public function reverses(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reverses_id');
    }

    // Scopes

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    public function scopeVoided($query)
    {
        return $query->where('status', 'voided');
    }

    public function scopeManual($query)
    {
        return $query->where('entry_type', 'manual');
    }

    public function scopeAuto($query)
    {
        return $query->where('entry_type', '!=', 'manual');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('entry_type', $type);
    }

    public function scopeByPeriod($query, int $periodId)
    {
        return $query->where('fiscal_period_id', $periodId);
    }

    public function scopeByReference($query, string $type, int $id)
    {
        return $query->where('reference_type', $type)->where('reference_id', $id);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('entry_date', [$startDate, $endDate]);
    }

    // Accessors

    public function getEntryTypeLabelAttribute(): string
    {
        return self::ENTRY_TYPES[$this->entry_type] ?? $this->entry_type;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'secondary';
    }

    public function getIsBalancedAttribute(): bool
    {
        return bccomp($this->total_debit, $this->total_credit, 2) === 0;
    }

    public function getIsDraftAttribute(): bool
    {
        return $this->status === 'draft';
    }

    public function getIsPostedAttribute(): bool
    {
        return $this->status === 'posted';
    }

    public function getIsVoidedAttribute(): bool
    {
        return $this->status === 'voided';
    }

    public function getIsManualAttribute(): bool
    {
        return $this->entry_type === 'manual';
    }

    public function getEntryDateFormattedAttribute(): string
    {
        return $this->entry_date?->format('d M Y') ?? '';
    }

    // Methods

    /**
     * Generate next entry number
     */
    public static function generateEntryNumber(): string
    {
        $year = date('Y');
        $prefix = "JE-{$year}-";

        $lastEntry = static::where('entry_number', 'like', "{$prefix}%")
            ->orderByRaw('CAST(SUBSTRING(entry_number, -5) AS UNSIGNED) DESC')
            ->first();

        if ($lastEntry) {
            $lastNumber = (int) substr($lastEntry->entry_number, -5);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate totals from lines
     */
    public function calculateTotals(): void
    {
        $totals = $this->lines()->selectRaw('
            SUM(debit_amount) as total_debit,
            SUM(credit_amount) as total_credit
        ')->first();

        $this->total_debit = $totals->total_debit ?? 0;
        $this->total_credit = $totals->total_credit ?? 0;
    }

    /**
     * Check if entry can be edited
     */
    public function canEdit(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if entry can be posted
     */
    public function canPost(): bool
    {
        if ($this->status !== 'draft') {
            return false;
        }

        // Must be balanced
        if (!$this->is_balanced) {
            return false;
        }

        // Must have at least 2 lines
        if ($this->lines()->count() < 2) {
            return false;
        }

        return true;
    }

    /**
     * Check if entry can be voided
     */
    public function canVoid(): bool
    {
        if ($this->status !== 'posted') {
            return false;
        }

        // Cannot void if already reversed
        if ($this->reversed_by_id) {
            return false;
        }

        return true;
    }

    /**
     * Check if entry can be deleted
     */
    public function canDelete(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Post the journal entry
     */
    public function post(int $userId): bool
    {
        if (!$this->canPost()) {
            return false;
        }

        DB::transaction(function () use ($userId) {
            $this->update([
                'status' => 'posted',
                'posted_by' => $userId,
                'posted_at' => now(),
            ]);

            // Update account balances
            foreach ($this->lines as $line) {
                AccountBalance::updateBalance(
                    $line->account_id,
                    $this->fiscal_period_id,
                    $line->debit_amount_base,
                    $line->credit_amount_base
                );
            }
        });

        return true;
    }

    /**
     * Void the journal entry
     */
    public function void(int $userId, string $reason): bool
    {
        if (!$this->canVoid()) {
            return false;
        }

        DB::transaction(function () use ($userId, $reason) {
            // Reverse the account balances
            foreach ($this->lines as $line) {
                AccountBalance::updateBalance(
                    $line->account_id,
                    $this->fiscal_period_id,
                    -$line->debit_amount_base,
                    -$line->credit_amount_base
                );
            }

            $this->update([
                'status' => 'voided',
                'voided_by' => $userId,
                'voided_at' => now(),
                'void_reason' => $reason,
            ]);
        });

        return true;
    }
}

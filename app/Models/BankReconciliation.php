<?php

namespace App\Models;

use App\Traits\LogsSystemChanges;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class BankReconciliation extends Model
{
    use HasFactory, LogsSystemChanges, BelongsToCompany;

    protected $table = 'fin_bank_reconciliations';

    protected $fillable = [
        'bank_account_id',
        'statement_date',
        'reconciliation_date',
        'statement_ending_balance',
        'book_balance',
        'reconciled_balance',
        'difference',
        'status',
        'notes',
        'created_by',
        'completed_by',
        'completed_at',
    ];

    protected $casts = [
        'statement_date' => 'date',
        'reconciliation_date' => 'date',
        'statement_ending_balance' => 'decimal:2',
        'book_balance' => 'decimal:2',
        'reconciled_balance' => 'decimal:2',
        'difference' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Boot model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
            $model->reconciliation_date = $model->reconciliation_date ?? now();
        });
    }

    /**
     * Relationships
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class, 'reconciliation_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Scopes
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('bank_account_id', $accountId);
    }

    /**
     * Accessors
     */
    public function getIsCompletedAttribute(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function getIsBalancedAttribute(): bool
    {
        return bccomp((string) abs((float) $this->difference), '0.01', 2) < 0;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED => 'success',
            self::STATUS_IN_PROGRESS => 'warning',
            self::STATUS_CANCELLED => 'danger',
            default => 'secondary',
        };
    }

    public function getReconciledCountAttribute(): int
    {
        return $this->transactions()->count();
    }

    /**
     * Get statuses
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /**
     * Mark reconciliation as completed
     */
    public function complete(): self
    {
        return DB::transaction(function () {
            $this->update([
                'status' => self::STATUS_COMPLETED,
                'completed_by' => auth()->id(),
                'completed_at' => now(),
            ]);

            // Update bank account last reconciled info
            $this->bankAccount->update([
                'last_reconciled_balance' => $this->reconciled_balance,
                'last_reconciled_date' => $this->statement_date,
            ]);

            // Mark all matched transactions as reconciled
            $this->transactions()->update([
                'reconciliation_status' => BankTransaction::STATUS_RECONCILED,
            ]);

            return $this->fresh();
        });
    }

    /**
     * Cancel reconciliation
     */
    public function cancel(): self
    {
        return DB::transaction(function () {
            // Unmark transactions
            $this->transactions()->update([
                'reconciliation_status' => BankTransaction::STATUS_UNRECONCILED,
                'reconciliation_id' => null,
            ]);

            $this->update([
                'status' => self::STATUS_CANCELLED,
            ]);

            return $this->fresh();
        });
    }

    /**
     * Calculate difference using bcmath for precision
     */
    public function calculateDifference(): string
    {
        $reconciledTotal = $this->transactions()->sum('amount');
        $this->reconciled_balance = bcadd((string) $this->book_balance, (string) $reconciledTotal, 2);
        $this->difference = bcsub((string) $this->statement_ending_balance, (string) $this->reconciled_balance, 2);
        $this->save();

        return $this->difference;
    }
}

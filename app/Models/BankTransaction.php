<?php

namespace App\Models;

use App\Traits\LogsSystemChanges;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankTransaction extends Model
{
    use HasFactory, LogsSystemChanges, BelongsToCompany;

    protected $table = 'fin_bank_transactions';

    protected $fillable = [
        'bank_account_id',
        'transaction_date',
        'reference',
        'description',
        'transaction_type',
        'amount',
        'running_balance',
        'source_type',
        'source_id',
        'journal_entry_id',
        'reconciliation_status',
        'reconciliation_id',
        'payee',
        'check_number',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'running_balance' => 'decimal:2',
    ];

    const TYPE_DEPOSIT = 'deposit';
    const TYPE_WITHDRAWAL = 'withdrawal';
    const TYPE_TRANSFER_IN = 'transfer_in';
    const TYPE_TRANSFER_OUT = 'transfer_out';
    const TYPE_FEE = 'fee';
    const TYPE_INTEREST = 'interest';
    const TYPE_ADJUSTMENT = 'adjustment';

    const STATUS_UNRECONCILED = 'unreconciled';
    const STATUS_MATCHED = 'matched';
    const STATUS_RECONCILED = 'reconciled';
    const STATUS_CLEARED = 'cleared';

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
        });

        // Update bank account balance after transaction
        static::created(function ($model) {
            $model->bankAccount->recalculateBalance();
        });

        static::deleted(function ($model) {
            $model->bankAccount->recalculateBalance();
        });
    }

    /**
     * Relationships
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class, 'reconciliation_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scopes
     */
    public function scopeUnreconciled($query)
    {
        return $query->where('reconciliation_status', self::STATUS_UNRECONCILED);
    }

    public function scopeReconciled($query)
    {
        return $query->where('reconciliation_status', self::STATUS_RECONCILED);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function scopeDeposits($query)
    {
        return $query->whereIn('transaction_type', [self::TYPE_DEPOSIT, self::TYPE_TRANSFER_IN, self::TYPE_INTEREST]);
    }

    public function scopeWithdrawals($query)
    {
        return $query->whereIn('transaction_type', [self::TYPE_WITHDRAWAL, self::TYPE_TRANSFER_OUT, self::TYPE_FEE]);
    }

    /**
     * Accessors
     */
    public function getIsDepositAttribute(): bool
    {
        return in_array($this->transaction_type, [self::TYPE_DEPOSIT, self::TYPE_TRANSFER_IN, self::TYPE_INTEREST]);
    }

    public function getIsWithdrawalAttribute(): bool
    {
        return in_array($this->transaction_type, [self::TYPE_WITHDRAWAL, self::TYPE_TRANSFER_OUT, self::TYPE_FEE]);
    }

    public function getSignedAmountAttribute(): float
    {
        return $this->is_deposit ? $this->amount : -$this->amount;
    }

    public function getTypeColorAttribute(): string
    {
        return match ($this->transaction_type) {
            self::TYPE_DEPOSIT, self::TYPE_TRANSFER_IN, self::TYPE_INTEREST => 'success',
            self::TYPE_WITHDRAWAL, self::TYPE_TRANSFER_OUT, self::TYPE_FEE => 'danger',
            self::TYPE_ADJUSTMENT => 'warning',
            default => 'secondary',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->reconciliation_status) {
            self::STATUS_RECONCILED, self::STATUS_CLEARED => 'success',
            self::STATUS_MATCHED => 'info',
            self::STATUS_UNRECONCILED => 'warning',
            default => 'secondary',
        };
    }

    /**
     * Get transaction types
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_DEPOSIT => 'Deposit',
            self::TYPE_WITHDRAWAL => 'Withdrawal',
            self::TYPE_TRANSFER_IN => 'Transfer In',
            self::TYPE_TRANSFER_OUT => 'Transfer Out',
            self::TYPE_FEE => 'Bank Fee',
            self::TYPE_INTEREST => 'Interest',
            self::TYPE_ADJUSTMENT => 'Adjustment',
        ];
    }

    /**
     * Get reconciliation statuses
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_UNRECONCILED => 'Unreconciled',
            self::STATUS_MATCHED => 'Matched',
            self::STATUS_RECONCILED => 'Reconciled',
            self::STATUS_CLEARED => 'Cleared',
        ];
    }
}

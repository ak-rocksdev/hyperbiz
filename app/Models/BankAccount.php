<?php

namespace App\Models;

use App\Traits\LogsSystemChanges;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model
{
    use HasFactory, LogsSystemChanges, BelongsToCompany;

    protected $table = 'fin_bank_accounts';

    protected $fillable = [
        'gl_account_id',
        'bank_name',
        'account_name',
        'account_number',
        'currency_code',
        'swift_code',
        'branch',
        'current_balance',
        'last_reconciled_balance',
        'last_reconciled_date',
        'is_active',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'current_balance' => 'decimal:2',
        'last_reconciled_balance' => 'decimal:2',
        'last_reconciled_date' => 'date',
        'is_active' => 'boolean',
    ];

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

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }

    /**
     * Relationships
     */
    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'gl_account_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class, 'bank_account_id');
    }

    public function reconciliations(): HasMany
    {
        return $this->hasMany(BankReconciliation::class, 'bank_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCurrency($query, string $currency)
    {
        return $query->where('currency_code', $currency);
    }

    /**
     * Accessors
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->bank_name} - {$this->account_name} ({$this->account_number})";
    }

    public function getMaskedAccountNumberAttribute(): string
    {
        $length = strlen($this->account_number);
        if ($length <= 4) {
            return $this->account_number;
        }
        return str_repeat('*', $length - 4) . substr($this->account_number, -4);
    }

    public function getUnreconciledBalanceAttribute(): float
    {
        return $this->transactions()
            ->where('reconciliation_status', 'unreconciled')
            ->sum('amount');
    }

    /**
     * Update current balance from transactions
     */
    public function recalculateBalance(): self
    {
        $balance = $this->transactions()
            ->selectRaw('SUM(CASE WHEN transaction_type IN (\'deposit\', \'transfer_in\', \'interest\') THEN amount ELSE -amount END) as balance')
            ->value('balance') ?? 0;

        $this->update(['current_balance' => $balance]);

        return $this->fresh();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToCompany;

class JournalEntryLine extends Model
{
    use BelongsToCompany;
    protected $table = 'fin_journal_entry_lines';

    protected $fillable = [
        'journal_entry_id',
        'account_id',
        'line_number',
        'description',
        'debit_amount',
        'credit_amount',
        'debit_amount_base',
        'credit_amount_base',
        'customer_id',
        'supplier_id',
        'product_id',
        'expense_id',
    ];

    protected $casts = [
        'line_number' => 'integer',
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'debit_amount_base' => 'decimal:2',
        'credit_amount_base' => 'decimal:2',
    ];

    // Relationships

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'customer_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'supplier_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class, 'expense_id');
    }

    // Scopes

    public function scopeDebitLines($query)
    {
        return $query->where('debit_amount', '>', 0);
    }

    public function scopeCreditLines($query)
    {
        return $query->where('credit_amount', '>', 0);
    }

    public function scopeByAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    // Accessors

    public function getIsDebitAttribute(): bool
    {
        return $this->debit_amount > 0;
    }

    public function getIsCreditAttribute(): bool
    {
        return $this->credit_amount > 0;
    }

    public function getNetAmountAttribute(): string
    {
        return bcsub((string) $this->debit_amount, (string) $this->credit_amount, 2);
    }

    public function getAmountAttribute(): float
    {
        return $this->debit_amount > 0 ? $this->debit_amount : $this->credit_amount;
    }

    // Methods

    /**
     * Calculate base currency amounts using bcmath for precision
     */
    public function calculateBaseAmounts(float $exchangeRate = 1): void
    {
        $this->debit_amount_base = bcmul((string) $this->debit_amount, (string) $exchangeRate, 2);
        $this->credit_amount_base = bcmul((string) $this->credit_amount, (string) $exchangeRate, 2);
    }
}

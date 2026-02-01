<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToCompany;

class AccountBalance extends Model
{
    use BelongsToCompany;
    protected $table = 'fin_account_balances';

    protected $fillable = [
        'account_id',
        'fiscal_period_id',
        'opening_debit',
        'opening_credit',
        'period_debit',
        'period_credit',
        'closing_debit',
        'closing_credit',
        'net_balance',
    ];

    protected $casts = [
        'opening_debit' => 'decimal:2',
        'opening_credit' => 'decimal:2',
        'period_debit' => 'decimal:2',
        'period_credit' => 'decimal:2',
        'closing_debit' => 'decimal:2',
        'closing_credit' => 'decimal:2',
        'net_balance' => 'decimal:2',
    ];

    // Relationships

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public function fiscalPeriod(): BelongsTo
    {
        return $this->belongsTo(FiscalPeriod::class, 'fiscal_period_id');
    }

    // Scopes

    public function scopeByAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    public function scopeByPeriod($query, int $periodId)
    {
        return $query->where('fiscal_period_id', $periodId);
    }

    // Accessors

    public function getOpeningBalanceAttribute(): float
    {
        return $this->opening_debit - $this->opening_credit;
    }

    public function getPeriodMovementAttribute(): float
    {
        return $this->period_debit - $this->period_credit;
    }

    public function getClosingBalanceAttribute(): float
    {
        return $this->closing_debit - $this->closing_credit;
    }

    // Methods

    /**
     * Recalculate closing balances
     */
    public function recalculateClosing(): void
    {
        $this->closing_debit = $this->opening_debit + $this->period_debit;
        $this->closing_credit = $this->opening_credit + $this->period_credit;
        $this->net_balance = $this->closing_debit - $this->closing_credit;
    }

    /**
     * Update balance for an account in a period
     */
    public static function updateBalance(int $accountId, ?int $periodId, float $debitAmount, float $creditAmount): void
    {
        if (!$periodId) {
            return;
        }

        $balance = static::firstOrCreate([
            'account_id' => $accountId,
            'fiscal_period_id' => $periodId,
        ], [
            'opening_debit' => 0,
            'opening_credit' => 0,
            'period_debit' => 0,
            'period_credit' => 0,
            'closing_debit' => 0,
            'closing_credit' => 0,
            'net_balance' => 0,
        ]);

        $balance->period_debit += $debitAmount;
        $balance->period_credit += $creditAmount;
        $balance->recalculateClosing();
        $balance->save();
    }

    /**
     * Get balance for account as of a specific period
     */
    public static function getBalanceAsOf(int $accountId, int $periodId): ?static
    {
        return static::where('account_id', $accountId)
            ->where('fiscal_period_id', $periodId)
            ->first();
    }

    /**
     * Carry forward opening balances from previous period
     */
    public static function carryForwardOpeningBalances(int $fromPeriodId, int $toPeriodId): void
    {
        $previousBalances = static::where('fiscal_period_id', $fromPeriodId)->get();

        foreach ($previousBalances as $prevBalance) {
            static::updateOrCreate(
                [
                    'account_id' => $prevBalance->account_id,
                    'fiscal_period_id' => $toPeriodId,
                ],
                [
                    'opening_debit' => $prevBalance->closing_debit,
                    'opening_credit' => $prevBalance->closing_credit,
                ]
            );
        }
    }

    /**
     * Get trial balance for a period
     */
    public static function getTrialBalance(int $periodId): \Illuminate\Support\Collection
    {
        return static::with('account')
            ->where('fiscal_period_id', $periodId)
            ->whereHas('account', function ($query) {
                $query->where('is_header', false);
            })
            ->get()
            ->sortBy('account.account_code');
    }
}

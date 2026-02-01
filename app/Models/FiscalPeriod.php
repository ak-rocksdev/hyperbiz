<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use App\Traits\BelongsToCompany;

class FiscalPeriod extends Model
{
    use HasFactory, BelongsToCompany;
    protected $table = 'fin_fiscal_periods';

    protected $fillable = [
        'fiscal_year_id',
        'name',
        'period_number',
        'start_date',
        'end_date',
        'status',
        'is_adjusting_period',
        'closed_by',
        'closed_at',
    ];

    protected $casts = [
        'period_number' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_adjusting_period' => 'boolean',
        'closed_at' => 'datetime',
    ];

    /**
     * Status labels
     */
    public const STATUS_LABELS = [
        'open' => 'Open',
        'closed' => 'Closed',
        'adjusting' => 'Adjusting',
        'locked' => 'Locked',
    ];

    public const STATUS_COLORS = [
        'open' => 'success',
        'closed' => 'warning',
        'adjusting' => 'info',
        'locked' => 'danger',
    ];

    // Relationships

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    // Scopes

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopePostable($query)
    {
        return $query->whereIn('status', ['open', 'adjusting']);
    }

    public function scopeForDate($query, Carbon $date)
    {
        return $query->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date);
    }

    // Accessors

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'secondary';
    }

    public function getIsOpenAttribute(): bool
    {
        return $this->status === 'open';
    }

    public function getIsClosedAttribute(): bool
    {
        return in_array($this->status, ['closed', 'locked']);
    }

    public function getIsLockedAttribute(): bool
    {
        return $this->status === 'locked';
    }

    public function getIsPostableAttribute(): bool
    {
        return in_array($this->status, ['open', 'adjusting']);
    }

    public function getDateRangeAttribute(): string
    {
        return $this->start_date->format('d M Y') . ' - ' . $this->end_date->format('d M Y');
    }

    public function getShortNameAttribute(): string
    {
        return $this->start_date->format('M Y');
    }

    // Methods

    /**
     * Get current fiscal period
     */
    public static function getCurrent(): ?self
    {
        $currentYear = FiscalYear::getCurrent();

        if (!$currentYear) {
            return null;
        }

        $today = Carbon::today();

        return static::where('fiscal_year_id', $currentYear->id)
            ->forDate($today)
            ->first();
    }

    /**
     * Alias for getCurrent
     */
    public static function getCurrentPeriod(): ?self
    {
        return static::getCurrent();
    }

    /**
     * Get fiscal period by date
     */
    public static function getByDate(Carbon $date): ?self
    {
        return static::forDate($date)
            ->whereHas('fiscalYear', function ($query) {
                $query->where('status', '!=', 'locked');
            })
            ->first();
    }

    /**
     * Check if a date falls within this period
     */
    public function containsDate(Carbon $date): bool
    {
        return $date->between($this->start_date, $this->end_date);
    }

    /**
     * Check if period can be closed
     */
    public function canClose(): bool
    {
        if ($this->is_closed || $this->is_locked) {
            return false;
        }

        // Check if all previous periods are closed
        $previousOpenPeriods = static::where('fiscal_year_id', $this->fiscal_year_id)
            ->where('period_number', '<', $this->period_number)
            ->where('status', 'open')
            ->exists();

        return !$previousOpenPeriods;
    }

    /**
     * Close the period
     */
    public function close(int $userId): bool
    {
        if (!$this->canClose()) {
            return false;
        }

        return $this->update([
            'status' => 'closed',
            'closed_by' => $userId,
            'closed_at' => now(),
        ]);
    }

    /**
     * Reopen the period
     */
    public function reopen(): bool
    {
        if ($this->status !== 'closed') {
            return false;
        }

        // Can't reopen if next period is closed
        $nextClosedPeriod = static::where('fiscal_year_id', $this->fiscal_year_id)
            ->where('period_number', '>', $this->period_number)
            ->whereIn('status', ['closed', 'locked'])
            ->exists();

        if ($nextClosedPeriod) {
            return false;
        }

        return $this->update([
            'status' => 'open',
            'closed_by' => null,
            'closed_at' => null,
        ]);
    }

    /**
     * Set as adjusting period
     */
    public function setAsAdjusting(): bool
    {
        if ($this->status !== 'closed') {
            return false;
        }

        return $this->update([
            'status' => 'adjusting',
            'is_adjusting_period' => true,
        ]);
    }
}

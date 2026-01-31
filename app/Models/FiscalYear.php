<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class FiscalYear extends Model
{
    use HasFactory;
    protected $table = 'fin_fiscal_years';

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'status',
        'is_current',
        'created_by',
        'closed_by',
        'closed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
        'closed_at' => 'datetime',
    ];

    /**
     * Status labels
     */
    public const STATUS_LABELS = [
        'open' => 'Open',
        'closed' => 'Closed',
        'locked' => 'Locked',
    ];

    public const STATUS_COLORS = [
        'open' => 'success',
        'closed' => 'warning',
        'locked' => 'danger',
    ];

    // Relationships

    public function periods(): HasMany
    {
        return $this->hasMany(FiscalPeriod::class)->orderBy('period_number');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
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
        return $this->status === 'closed';
    }

    public function getIsLockedAttribute(): bool
    {
        return $this->status === 'locked';
    }

    public function getDateRangeAttribute(): string
    {
        return $this->start_date->format('d M Y') . ' - ' . $this->end_date->format('d M Y');
    }

    // Methods

    /**
     * Get current fiscal year
     */
    public static function getCurrent(): ?self
    {
        return static::where('is_current', true)->first();
    }

    /**
     * Get fiscal year by date
     */
    public static function getByDate(Carbon $date): ?self
    {
        return static::where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();
    }

    /**
     * Set this fiscal year as current
     */
    public function setAsCurrent(): bool
    {
        // Remove current flag from all other years
        static::where('id', '!=', $this->id)->update(['is_current' => false]);

        return $this->update(['is_current' => true]);
    }

    /**
     * Create periods for this fiscal year
     */
    public function createPeriods(): void
    {
        $startDate = $this->start_date->copy();
        $periodNumber = 1;

        while ($startDate->lessThan($this->end_date)) {
            $endDate = $startDate->copy()->endOfMonth();

            // Make sure end date doesn't exceed fiscal year end
            if ($endDate->greaterThan($this->end_date)) {
                $endDate = $this->end_date->copy();
            }

            FiscalPeriod::create([
                'fiscal_year_id' => $this->id,
                'name' => $startDate->format('F Y'),
                'period_number' => $periodNumber,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'status' => 'open',
            ]);

            $startDate = $endDate->copy()->addDay()->startOfDay();
            $periodNumber++;
        }
    }

    /**
     * Check if fiscal year can be closed
     */
    public function canClose(): bool
    {
        // All periods must be closed
        return !$this->periods()->where('status', 'open')->exists();
    }

    /**
     * Close the fiscal year
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
     * Lock the fiscal year (permanent)
     */
    public function lock(): bool
    {
        if ($this->status !== 'closed') {
            return false;
        }

        // Lock all periods
        $this->periods()->update(['status' => 'locked']);

        return $this->update(['status' => 'locked']);
    }
}

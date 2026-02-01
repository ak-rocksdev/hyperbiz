<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_monthly',
        'price_yearly',
        'max_users',
        'max_products',
        'max_customers',
        'max_monthly_orders',
        'features',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'max_users' => 'integer',
            'max_products' => 'integer',
            'max_customers' => 'integer',
            'max_monthly_orders' => 'integer',
            'features' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    /**
     * Get all companies subscribed to this plan.
     */
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    /**
     * Check if a limit is unlimited (0 = unlimited).
     */
    public function isUnlimited(string $limit): bool
    {
        return $this->$limit === 0;
    }

    /**
     * Get formatted price monthly.
     */
    public function getFormattedPriceMonthlyAttribute(): string
    {
        return number_format($this->price_monthly, 2);
    }

    /**
     * Get formatted price yearly.
     */
    public function getFormattedPriceYearlyAttribute(): string
    {
        return number_format($this->price_yearly, 2);
    }

    /**
     * Get discount percentage for yearly vs monthly.
     */
    public function getYearlyDiscountPercentageAttribute(): int
    {
        if ($this->price_monthly == 0) {
            return 0;
        }

        $monthlyTotal = $this->price_monthly * 12;
        $savings = $monthlyTotal - $this->price_yearly;

        return (int) round(($savings / $monthlyTotal) * 100);
    }

    /**
     * Scope to get only active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('price_monthly');
    }
}

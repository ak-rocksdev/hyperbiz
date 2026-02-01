<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsSystemChanges;
use Carbon\Carbon;

class Company extends Model
{
    use HasFactory, LogsSystemChanges;

    protected $table = 'mst_company';

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'website',
        'industry',
        'logo',
        'subscription_status',
        'subscription_plan_id',
        'trial_ends_at',
        'subscription_starts_at',
        'subscription_ends_at',
        'billing_cycle',
        'max_users',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'subscription_starts_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'max_users' => 'integer',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_by = auth()->id();

            // Set default trial period
            if (empty($model->subscription_status)) {
                $model->subscription_status = 'trial';
            }
            if (empty($model->trial_ends_at)) {
                $model->trial_ends_at = Carbon::now()->addDays(config('app.trial_days', 14));
            }
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });
    }

    /**
     * Get all users belonging to this company.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the subscription plan this company is subscribed to.
     */
    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    /**
     * Get all invoices for this company.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get all payment transactions for this company.
     */
    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    /**
     * Get all payment proofs for this company.
     */
    public function paymentProofs(): HasMany
    {
        return $this->hasMany(PaymentProof::class);
    }

    /**
     * Get all sales orders for this company.
     */
    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    /**
     * Get all products for this company.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get all customers for this company.
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Check if the company is on trial.
     */
    public function isOnTrial(): bool
    {
        return $this->subscription_status === 'trial'
            && $this->trial_ends_at
            && $this->trial_ends_at->isFuture();
    }

    /**
     * Check if the company has an active subscription.
     */
    public function isSubscriptionActive(): bool
    {
        return $this->subscription_status === 'active';
    }

    /**
     * Check if the subscription/trial has expired.
     */
    public function isExpired(): bool
    {
        if ($this->subscription_status === 'expired') {
            return true;
        }

        if ($this->subscription_status === 'trial' && $this->trial_ends_at && $this->trial_ends_at->isPast()) {
            return true;
        }

        return false;
    }

    /**
     * Check if the company is in read-only mode.
     */
    public function isReadOnly(): bool
    {
        return in_array($this->subscription_status, ['expired', 'suspended']) || $this->isExpired();
    }

    /**
     * Get the number of days remaining in trial.
     */
    public function trialDaysRemaining(): int
    {
        if (!$this->isOnTrial() || !$this->trial_ends_at) {
            return 0;
        }

        return max(0, Carbon::now()->diffInDays($this->trial_ends_at, false));
    }

    /**
     * Get the subscription status display text.
     */
    public function getSubscriptionStatusLabelAttribute(): string
    {
        return match ($this->subscription_status) {
            'trial' => $this->isOnTrial() ? 'Trial' : 'Trial Expired',
            'active' => 'Active',
            'expired' => 'Expired',
            'suspended' => 'Suspended',
            'cancelled' => 'Cancelled',
            default => 'Unknown',
        };
    }
}

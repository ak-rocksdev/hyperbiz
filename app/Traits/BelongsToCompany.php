<?php

namespace App\Traits;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCompany
{
    /**
     * Boot the trait.
     */
    protected static function bootBelongsToCompany(): void
    {
        // Global scope: automatically filter by company_id for non-platform admins
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && !auth()->user()->isPlatformAdmin()) {
                $builder->where($builder->getModel()->getTable() . '.company_id', auth()->user()->company_id);
            }
        });

        // Auto-set company_id on creating
        static::creating(function (Model $model) {
            if (auth()->check() && empty($model->company_id)) {
                $model->company_id = auth()->user()->company_id;
            }
        });
    }

    /**
     * Initialize the trait.
     */
    public function initializeBelongsToCompany(): void
    {
        // Add company_id to fillable if not already present
        if (!in_array('company_id', $this->fillable)) {
            $this->fillable[] = 'company_id';
        }
    }

    /**
     * Get the company that owns this model.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope a query to a specific company.
     */
    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where($this->getTable() . '.company_id', $companyId);
    }

    /**
     * Scope to remove the company global scope (for platform admin queries).
     */
    public function scopeWithoutCompanyScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('company');
    }
}

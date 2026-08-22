<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Belongs To Tenant Trait
 *
 * Provides multi-tenancy support for models.
 */
trait BelongsToTenant
{
    /**
     * Boot the trait.
     */
    protected static function bootBelongsToTenant()
    {
        // Automatically scope queries to current tenant
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check() && auth()->user()->tenant_id) {
                $builder->where('tenant_id', auth()->user()->tenant_id);
            }
        });

        // Automatically set tenant_id when creating
        static::creating(function ($model) {
            if (auth()->check() && auth()->user()->tenant_id && !$model->tenant_id) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
    }

    /**
     * Get the tenant that owns the model.
     */
    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /**
     * Scope a query to a specific tenant.
     */
    public function scopeForTenant(Builder $query, $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope a query to the current tenant.
     */
    public function scopeCurrentTenant(Builder $query): Builder
    {
        if (auth()->check() && auth()->user()->tenant_id) {
            return $query->where('tenant_id', auth()->user()->tenant_id);
        }

        return $query;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tenant Model
 *
 * Represents a tenant (organization/school) in the multi-tenant system.
 */
class Tenant extends Model
{
    use HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'slug',
        'domain',
        'database',
        'settings',
        'is_active',
        'subscription_plan',
        'subscription_status',
        'subscription_expires_at',
        'max_users',
        'max_students',
        'max_storage_gb',
        'timezone',
        'currency',
        'language',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'subscription_expires_at' => 'datetime',
    ];

    /**
     * Get the users belonging to this tenant.
     */
    public function users(): HasMany
    {
        return $this->hasMany(\App\Modules\Iam\Models\User::class);
    }

    /**
     * Get the branches belonging to this tenant.
     */
    public function branches(): HasMany
    {
        return $this->hasMany(\App\Modules\Iam\Models\Branch::class);
    }

    /**
     * Get the students belonging to this tenant.
     */
    public function students(): HasMany
    {
        return $this->hasMany(\App\Modules\Academic\Models\Student::class);
    }

    /**
     * Get the teachers belonging to this tenant.
     */
    public function teachers(): HasMany
    {
        return $this->hasMany(\App\Modules\PeopleHR\Models\Teacher::class);
    }

    /**
     * Check if tenant is active.
     */
    public function isActive(): bool
    {
        return $this->is_active && 
               (!$this->subscription_expires_at || $this->subscription_expires_at->isFuture());
    }

    /**
     * Check if tenant has reached user limit.
     */
    public function hasReachedUserLimit(): bool
    {
        if (!$this->max_users) {
            return false;
        }

        return $this->users()->count() >= $this->max_users;
    }

    /**
     * Check if tenant has reached student limit.
     */
    public function hasReachedStudentLimit(): bool
    {
        if (!$this->max_students) {
            return false;
        }

        return $this->students()->count() >= $this->max_students;
    }

    /**
     * Get current storage usage in GB.
     */
    public function getStorageUsageGbAttribute(): float
    {
        // Calculate actual storage usage
        // This would typically query a storage service or calculate from file sizes
        return 0.0;
    }

    /**
     * Check if tenant has reached storage limit.
     */
    public function hasReachedStorageLimit(): bool
    {
        if (!$this->max_storage_gb) {
            return false;
        }

        return $this->storage_usage_gb >= $this->max_storage_gb;
    }

    /**
     * Get tenant settings.
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Set tenant setting.
     */
    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->update(['settings' => $settings]);
    }

    /**
     * Scope a query to only include active tenants.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('subscription_expires_at')
                    ->orWhere('subscription_expires_at', '>', now());
            });
    }

    /**
     * Generate unique slug from name.
     */
    public static function generateSlug(string $name): string
    {
        $slug = \Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}

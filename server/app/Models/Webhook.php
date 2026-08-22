<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Webhook Model
 *
 * Stores webhook configurations for external integrations.
 */
class Webhook extends Model
{
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'url',
        'secret',
        'events',
        'is_active',
        'branch_id',
        'created_by',
        'last_triggered_at',
        'failure_count',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
    ];

    /**
     * Get the branch that owns the webhook.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Iam\Models\Branch::class);
    }

    /**
     * Get the user who created the webhook.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Iam\Models\User::class, 'created_by');
    }

    /**
     * Get the webhook deliveries.
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    /**
     * Check if webhook is subscribed to an event.
     */
    public function isSubscribedTo(string $event): bool
    {
        return in_array($event, $this->events);
    }

    /**
     * Mark webhook as triggered.
     */
    public function markAsTriggered(): void
    {
        $this->update([
            'last_triggered_at' => now(),
        ]);
    }

    /**
     * Increment failure count.
     */
    public function incrementFailureCount(): void
    {
        $this->increment('failure_count');
        
        // Auto-disable after 10 consecutive failures
        if ($this->failure_count >= 10) {
            $this->update(['is_active' => false]);
        }
    }

    /**
     * Reset failure count.
     */
    public function resetFailureCount(): void
    {
        $this->update(['failure_count' => 0]);
    }

    /**
     * Scope a query to only include active webhooks.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include webhooks for a specific event.
     */
    public function scopeForEvent($query, string $event)
    {
        return $query->whereJsonContains('events', $event);
    }

    /**
     * Generate a signature for the payload.
     */
    public function generateSignature(string $payload): string
    {
        return hash_hmac('sha256', $payload, $this->secret);
    }
}

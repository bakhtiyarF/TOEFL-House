<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Webhook Delivery Model
 *
 * Tracks webhook delivery attempts.
 */
class WebhookDelivery extends Model
{
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'webhook_id',
        'event',
        'payload',
        'response_status',
        'response_body',
        'response_headers',
        'delivered_at',
        'success',
        'error_message',
        'attempt_count',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'payload' => 'array',
        'response_headers' => 'array',
        'delivered_at' => 'datetime',
        'success' => 'boolean',
    ];

    /**
     * Get the webhook that owns the delivery.
     */
    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }

    /**
     * Scope a query to only include successful deliveries.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    /**
     * Scope a query to only include failed deliveries.
     */
    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }

    /**
     * Scope a query to only include deliveries for a specific event.
     */
    public function scopeForEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Mark delivery as successful.
     */
    public function markAsSuccessful(int $statusCode, string $responseBody, array $responseHeaders): void
    {
        $this->update([
            'response_status' => $statusCode,
            'response_body' => $responseBody,
            'response_headers' => $responseHeaders,
            'delivered_at' => now(),
            'success' => true,
        ]);

        $this->webhook->resetFailureCount();
    }

    /**
     * Mark delivery as failed.
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'delivered_at' => now(),
            'success' => false,
            'error_message' => $errorMessage,
        ]);

        $this->webhook->incrementFailureCount();
    }

    /**
     * Increment attempt count.
     */
    public function incrementAttemptCount(): void
    {
        $this->increment('attempt_count');
    }
}

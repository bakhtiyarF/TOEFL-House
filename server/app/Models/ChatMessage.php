<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Chat Message Model
 *
 * Stores chat messages for real-time messaging.
 */
class ChatMessage extends Model
{
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'message',
        'message_type',
        'attachments',
        'read_at',
        'deleted_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'attachments' => 'array',
        'read_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the conversation that owns the message.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class);
    }

    /**
     * Get the sender of the message.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Iam\Models\User::class, 'sender_id');
    }

    /**
     * Get the message reactions.
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(ChatMessageReaction::class);
    }

    /**
     * Mark message as read.
     */
    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }

    /**
     * Soft delete message.
     */
    public function softDelete(): void
    {
        $this->update(['deleted_at' => now()]);
    }

    /**
     * Scope a query to only include unread messages.
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope a query to only include read messages.
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope a query to only include non-deleted messages.
     */
    public function scopeNotDeleted($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Check if message is read.
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Check if message is deleted.
     */
    public function isDeleted(): bool
    {
        return $this->deleted_at !== null;
    }

    /**
     * Get formatted message with sender info.
     */
    public function getFormattedMessageAttribute(): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender_id' => $this->sender_id,
            'sender_name' => $this->sender->full_name ?? 'Unknown',
            'sender_avatar' => $this->sender->avatar ?? null,
            'message' => $this->message,
            'message_type' => $this->message_type,
            'attachments' => $this->attachments ?? [],
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'is_read' => $this->isRead(),
            'is_deleted' => $this->isDeleted(),
        ];
    }
}

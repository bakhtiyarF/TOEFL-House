<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Chat Conversation Model
 *
 * Stores chat conversations between users.
 */
class ChatConversation extends Model
{
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'type',
        'created_by',
        'is_group',
        'avatar',
        'description',
        'last_message_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_group' => 'boolean',
        'last_message_at' => 'datetime',
    ];

    /**
     * Get the participants of the conversation.
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(\App\Modules\Iam\Models\User::class, 'chat_conversation_participants')
            ->withPivot(['joined_at', 'left_at', 'last_read_at', 'notifications_enabled'])
            ->withTimestamps();
    }

    /**
     * Get the messages in the conversation.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    /**
     * Get the creator of the conversation.
     */
    public function creator()
    {
        return $this->belongsTo(\App\Modules\Iam\Models\User::class, 'created_by');
    }

    /**
     * Add participant to conversation.
     */
    public function addParticipant($userId): void
    {
        $this->participants()->attach($userId, [
            'joined_at' => now(),
            'notifications_enabled' => true,
        ]);
    }

    /**
     * Remove participant from conversation.
     */
    public function removeParticipant($userId): void
    {
        $this->participants()->updateExistingPivot($userId, [
            'left_at' => now(),
        ]);
    }

    /**
     * Update last message timestamp.
     */
    public function updateLastMessageAt(): void
    {
        $this->update(['last_message_at' => now()]);
    }

    /**
     * Get unread message count for a user.
     */
    public function getUnreadCount($userId): int
    {
        $participant = $this->participants()->where('user_id', $userId)->first();
        
        if (!$participant) {
            return 0;
        }

        $lastReadAt = $participant->pivot->last_read_at;

        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->whereNull('deleted_at')
            ->when($lastReadAt, function ($query) use ($lastReadAt) {
                $query->where('created_at', '>', $lastReadAt);
            })
            ->count();
    }

    /**
     * Mark all messages as read for a user.
     */
    public function markAsRead($userId): void
    {
        $this->participants()->updateExistingPivot($userId, [
            'last_read_at' => now(),
        ]);
    }

    /**
     * Check if user is a participant.
     */
    public function isParticipant($userId): bool
    {
        return $this->participants()
            ->where('user_id', $userId)
            ->whereNull('left_at')
            ->exists();
    }

    /**
     * Get latest message.
     */
    public function getLatestMessageAttribute()
    {
        return $this->messages()
            ->whereNull('deleted_at')
            ->latest()
            ->first();
    }

    /**
     * Scope a query to only include active conversations.
     */
    public function scopeActive($query)
    {
        return $query->whereHas('participants', function ($q) {
            $q->whereNull('left_at');
        });
    }

    /**
     * Scope a query to only include group conversations.
     */
    public function scopeGroups($query)
    {
        return $query->where('is_group', true);
    }

    /**
     * Scope a query to only include direct conversations.
     */
    public function scopeDirect($query)
    {
        return $query->where('is_group', false);
    }

    /**
     * Get formatted conversation with latest message.
     */
    public function getFormattedConversationAttribute(): array
    {
        $latestMessage = $this->latest_message;

        return [
            'id' => $this->id,
            'name' => $this->name ?? 'Conversation',
            'type' => $this->type,
            'is_group' => $this->is_group,
            'avatar' => $this->avatar,
            'description' => $this->description,
            'participants_count' => $this->participants()->whereNull('left_at')->count(),
            'last_message' => $latestMessage ? $latestMessage->formatted_message : null,
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}

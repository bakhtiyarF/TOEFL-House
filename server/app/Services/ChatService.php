<?php

namespace App\Services;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Events\MessageSent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Chat Service
 *
 * Provides real-time chat functionality.
 */
class ChatService
{
    /**
     * Create a new conversation.
     */
    public function createConversation(array $participantIds, ?string $name = null, bool $isGroup = false, ?string $description = null): ChatConversation
    {
        try {
            return DB::transaction(function () use ($participantIds, $name, $isGroup, $description) {
                $conversation = ChatConversation::create([
                    'id' => Str::uuid(),
                    'name' => $name,
                    'type' => $isGroup ? 'group' : 'direct',
                    'is_group' => $isGroup,
                    'description' => $description,
                    'created_by' => auth()->id(),
                ]);

                // Add participants
                foreach ($participantIds as $userId) {
                    $conversation->addParticipant($userId);
                }

                Log::info("Chat conversation created", [
                    'conversation_id' => $conversation->id,
                    'participants' => $participantIds,
                    'is_group' => $isGroup,
                ]);

                return $conversation;
            });
        } catch (\Exception $e) {
            Log::error("Failed to create chat conversation", [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Send a message in a conversation.
     */
    public function sendMessage(string $conversationId, string $senderId, string $message, string $messageType = 'text', ?array $attachments = null): ChatMessage
    {
        try {
            return DB::transaction(function () use ($conversationId, $senderId, $message, $messageType, $attachments) {
                $conversation = ChatConversation::findOrFail($conversationId);

                // Check if sender is a participant
                if (!$conversation->isParticipant($senderId)) {
                    throw new \Exception("User is not a participant in this conversation.");
                }

                $chatMessage = ChatMessage::create([
                    'id' => Str::uuid(),
                    'conversation_id' => $conversationId,
                    'sender_id' => $senderId,
                    'message' => $message,
                    'message_type' => $messageType,
                    'attachments' => $attachments,
                ]);

                // Update conversation last message timestamp
                $conversation->updateLastMessageAt();

                // Broadcast message sent event
                event(new MessageSent($chatMessage));

                Log::info("Chat message sent", [
                    'message_id' => $chatMessage->id,
                    'conversation_id' => $conversationId,
                    'sender_id' => $senderId,
                ]);

                return $chatMessage;
            });
        } catch (\Exception $e) {
            Log::error("Failed to send chat message", [
                'conversation_id' => $conversationId,
                'sender_id' => $senderId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get conversation messages with pagination.
     */
    public function getConversationMessages(string $conversationId, int $perPage = 50, ?string $before = null): array
    {
        $query = ChatMessage::where('conversation_id', $conversationId)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at');

        if ($before) {
            $query->where('created_at', '<', $before);
        }

        $messages = $query->limit($perPage)->get();

        return [
            'messages' => $messages->map(fn($msg) => $msg->formatted_message)->reverse()->values()->toArray(),
            'has_more' => $messages->count() === $perPage,
        ];
    }

    /**
     * Mark conversation as read for a user.
     */
    public function markConversationAsRead(string $conversationId, string $userId): void
    {
        $conversation = ChatConversation::findOrFail($conversationId);
        $conversation->markAsRead($userId);

        Log::info("Conversation marked as read", [
            'conversation_id' => $conversationId,
            'user_id' => $userId,
        ]);
    }

    /**
     * Get user's conversations.
     */
    public function getUserConversations(string $userId): array
    {
        $conversations = ChatConversation::whereHas('participants', function ($query) use ($userId) {
                $query->where('user_id', $userId)->whereNull('left_at');
            })
            ->orderByDesc('last_message_at')
            ->get();

        return $conversations->map(function ($conversation) use ($userId) {
            $formatted = $conversation->formatted_conversation;
            $formatted['unread_count'] = $conversation->getUnreadCount($userId);
            return $formatted;
        })->toArray();
    }

    /**
     * Get total unread message count for a user.
     */
    public function getTotalUnreadCount(string $userId): int
    {
        $conversations = ChatConversation::whereHas('participants', function ($query) use ($userId) {
            $query->where('user_id', $userId)->whereNull('left_at');
        })->get();

        return $conversations->sum(function ($conversation) use ($userId) {
            return $conversation->getUnreadCount($userId);
        });
    }

    /**
     * Add participant to conversation.
     */
    public function addParticipant(string $conversationId, string $userId): void
    {
        $conversation = ChatConversation::findOrFail($conversationId);
        
        if (!$conversation->is_group) {
            throw new \Exception("Cannot add participants to direct conversations.");
        }

        $conversation->addParticipant($userId);

        Log::info("Participant added to conversation", [
            'conversation_id' => $conversationId,
            'user_id' => $userId,
        ]);
    }

    /**
     * Remove participant from conversation.
     */
    public function removeParticipant(string $conversationId, string $userId): void
    {
        $conversation = ChatConversation::findOrFail($conversationId);
        $conversation->removeParticipant($userId);

        Log::info("Participant removed from conversation", [
            'conversation_id' => $conversationId,
            'user_id' => $userId,
        ]);
    }

    /**
     * Delete a message (soft delete).
     */
    public function deleteMessage(string $messageId, string $userId): void
    {
        $message = ChatMessage::findOrFail($messageId);

        // Only sender can delete their own messages
        if ($message->sender_id !== $userId) {
            throw new \Exception("You can only delete your own messages.");
        }

        $message->softDelete();

        Log::info("Chat message deleted", [
            'message_id' => $messageId,
            'user_id' => $userId,
        ]);
    }

    /**
     * Get or create direct conversation between two users.
     */
    public function getOrCreateDirectConversation(string $userId1, string $userId2): ChatConversation
    {
        // Try to find existing direct conversation
        $conversation = ChatConversation::where('is_group', false)
            ->whereHas('participants', function ($query) use ($userId1) {
                $query->where('user_id', $userId1)->whereNull('left_at');
            })
            ->whereHas('participants', function ($query) use ($userId2) {
                $query->where('user_id', $userId2)->whereNull('left_at');
            })
            ->first();

        if ($conversation) {
            return $conversation;
        }

        // Create new direct conversation
        return $this->createConversation([$userId1, $userId2], null, false);
    }
}

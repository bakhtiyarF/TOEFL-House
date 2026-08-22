<?php

namespace App\Modules\PlatformServices\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Notification Service
 *
 * Manages in-app notifications for the platform.
 * Supports creating, reading, and marking notifications as read.
 */
class NotificationService
{
    /**
     * Create a notification
     */
    public function create(
        string $title,
        string $message,
        string $type = 'info',
        ?string $userId = null,
        ?string $branchId = null,
        ?string $link = null,
    ): string {
        $id = Str::uuid()->toString();

        DB::table('notifications')->insert([
            'id' => $id,
            'title' => $title,
            'message' => $message,
            'date' => now()->toDateString(),
            'read' => false,
            'type' => $type,
            'user_id' => $userId,
            'link' => $link,
            'branch_id' => $branchId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    /**
     * Get unread notifications for a user
     */
    public function getUnread(string $userId, int $limit = 20): array
    {
        return DB::table('notifications')
            ->where('user_id', $userId)
            ->where('read', false)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get all notifications for a user
     */
    public function getAll(string $userId, int $limit = 50): array
    {
        return DB::table('notifications')
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)->orWhereNull('user_id');
            })
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(string $notificationId): void
    {
        DB::table('notifications')
            ->where('id', $notificationId)
            ->update(['read' => true, 'updated_at' => now()]);
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(string $userId): int
    {
        return DB::table('notifications')
            ->where('user_id', $userId)
            ->where('read', false)
            ->update(['read' => true, 'updated_at' => now()]);
    }

    /**
     * Get unread count for a user
     */
    public function getUnreadCount(string $userId): int
    {
        return DB::table('notifications')
            ->where('user_id', $userId)
            ->where('read', false)
            ->count();
    }

    /**
     * Create standard notifications from domain events (08 §6)
     */
    public function studentRegistered(string $studentName, string $branchId): string
    {
        return $this->create(
            title: 'New Student Registered',
            message: "{$studentName} has been registered in the system.",
            type: 'success',
            branchId: $branchId,
        );
    }

    public function paymentReceived(float $amount, string $branchId): string
    {
        return $this->create(
            title: 'Payment Received',
            message: number_format($amount) . ' AFN payment recorded.',
            type: 'info',
            branchId: $branchId,
        );
    }

    public function attendanceWarning(string $className, float $rate, string $branchId): string
    {
        return $this->create(
            title: 'Attendance Warning',
            message: "{$className}: attendance at {$rate}% — below 85% threshold.",
            type: 'warning',
            branchId: $branchId,
        );
    }

    public function lowStockAlert(string $bookTitle, int $stock, string $branchId): string
    {
        return $this->create(
            title: $stock === 0 ? 'Out of Stock' : 'Low Stock Alert',
            message: "{$bookTitle}: {$stock} remaining in stock.",
            type: $stock === 0 ? 'critical' : 'warning',
            branchId: $branchId,
        );
    }
}

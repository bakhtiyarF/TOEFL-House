<?php

namespace App\Modules\PlatformServices\Http\Controllers;

use App\Modules\PlatformServices\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $notifications = $this->notificationService->getAll($userId);
        $unreadCount = $this->notificationService->getUnreadCount($userId);

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $this->notificationService->markAsRead($id);
        return response()->json(['message' => 'Marked as read']);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead($request->user()->id);
        return response()->json(['message' => "Marked {$count} notifications as read"]);
    }
}

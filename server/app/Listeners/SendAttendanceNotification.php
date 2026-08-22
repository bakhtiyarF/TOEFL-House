<?php

namespace App\Listeners;

use App\Events\AttendanceRecorded;
use App\Modules\PlatformServices\Models\Notification;
use App\Services\NotificationPreferenceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Send Attendance Notification Listener
 *
 * Sends notifications when attendance is recorded.
 */
class SendAttendanceNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private NotificationPreferenceService $notificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(AttendanceRecorded $event): void
    {
        $roster = $event->roster;
        $student = $roster->student;
        $session = $roster->session;

        // Check if notification should be sent
        if ($student->user && !$this->notificationService->shouldNotify($student->user, 'in_app', 'attendance_warning')) {
            return;
        }

        // Only send notification for absent or late
        if (!in_array($roster->attendance_status, ['absent', 'late'])) {
            return;
        }

        try {
            Notification::create([
                'type' => 'attendance_warning',
                'title' => 'Attendance Alert',
                'message' => "You were marked {$roster->attendance_status} for {$session->class->name} on {$session->session_date->format('M j, Y')}.",
                'user_id' => $student->user_id,
                'branch_id' => $student->branch_id,
                'data' => [
                    'student_id' => $student->id,
                    'session_id' => $session->id,
                    'class_id' => $session->class_id,
                    'attendance_status' => $roster->attendance_status,
                ],
            ]);

            Log::info("Attendance notification sent to student {$student->student_code}");
        } catch (\Exception $e) {
            Log::error("Failed to send attendance notification: " . $e->getMessage());
        }
    }
}

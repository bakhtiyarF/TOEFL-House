<?php

namespace App\Listeners;

use App\Events\AttendanceRecorded;
use App\Modules\Iam\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Send Attendance Recorded Notification Listener
 *
 * Sends notifications when attendance is recorded.
 */
class SendAttendanceRecordedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(AttendanceRecorded $event): void
    {
        $roster = $event->roster;
        $student = $roster->student;
        $session = $roster->session;

        try {
            // Notify student if absent
            if ($roster->attendance_status === 'absent' && $student && $student->user) {
                Notification::create([
                    'user_id' => $student->user->id,
                    'type' => 'warning',
                    'title' => 'Attendance Marked Absent',
                    'message' => "You were marked absent for {$session->class->name} on {$session->session_date->format('M j, Y')}.",
                    'data' => [
                        'roster_id' => $roster->id,
                        'session_id' => $session->id,
                        'class_name' => $session->class->name,
                        'session_date' => $session->session_date->toIso8601String(),
                    ],
                    'action_url' => "/attendance",
                    'read' => false,
                ]);
            }

            Log::info("Attendance recorded notification sent for roster {$roster->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send attendance recorded notification: " . $e->getMessage());
        }
    }
}

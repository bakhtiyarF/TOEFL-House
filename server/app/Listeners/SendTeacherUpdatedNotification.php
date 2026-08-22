<?php

namespace App\Listeners;

use App\Events\TeacherUpdated;
use App\Modules\Iam\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Send Teacher Updated Notification Listener
 *
 * Sends notifications when a teacher is updated.
 */
class SendTeacherUpdatedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(TeacherUpdated $event): void
    {
        $teacher = $event->teacher;

        try {
            // Notify branch administrators
            $admins = $teacher->branch->users()
                ->whereHas('roles', function ($query) {
                    $query->whereIn('name', ['admin', 'hr_manager']);
                })
                ->get();

            foreach ($admins as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'type' => 'info',
                    'title' => 'Teacher Updated',
                    'message' => "Teacher '{$teacher->full_name}' has been updated.",
                    'data' => [
                        'teacher_id' => $teacher->id,
                        'teacher_name' => $teacher->full_name,
                    ],
                    'action_url' => "/teachers/{$teacher->id}",
                    'read' => false,
                ]);
            }

            Log::info("Teacher updated notifications sent for teacher {$teacher->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send teacher updated notifications: " . $e->getMessage());
        }
    }
}

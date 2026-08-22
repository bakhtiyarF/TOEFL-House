<?php

namespace App\Listeners;

use App\Events\TeacherCreated;
use App\Modules\Iam\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Send Teacher Created Notification Listener
 *
 * Sends notifications when a new teacher is created.
 */
class SendTeacherCreatedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(TeacherCreated $event): void
    {
        $teacher = $event->teacher;

        try {
            // Notify the teacher if they have a user account
            if ($teacher->user) {
                Notification::create([
                    'user_id' => $teacher->user->id,
                    'type' => 'success',
                    'title' => 'Welcome to TOEFL House',
                    'message' => "Welcome {$teacher->full_name}! Your teacher profile has been created.",
                    'data' => [
                        'teacher_id' => $teacher->id,
                        'teacher_name' => $teacher->full_name,
                    ],
                    'action_url' => "/teachers/{$teacher->id}",
                    'read' => false,
                ]);

                Log::info("Teacher welcome notification sent to {$teacher->user->email}");
            }

            // Notify branch administrators
            $admins = $teacher->branch->users()
                ->whereHas('roles', function ($query) {
                    $query->whereIn('name', ['admin', 'branch_manager']);
                })
                ->get();

            foreach ($admins as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'type' => 'info',
                    'title' => 'New Teacher Added',
                    'message' => "A new teacher '{$teacher->full_name}' has been added to your branch.",
                    'data' => [
                        'teacher_id' => $teacher->id,
                        'teacher_name' => $teacher->full_name,
                    ],
                    'action_url' => "/teachers/{$teacher->id}",
                    'read' => false,
                ]);
            }

            Log::info("Teacher created notifications sent for teacher {$teacher->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send teacher created notifications: " . $e->getMessage());
        }
    }
}

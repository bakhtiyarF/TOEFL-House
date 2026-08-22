<?php

namespace App\Listeners;

use App\Events\StudentEnrolled;
use App\Modules\Iam\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Send Student Enrolled Notification Listener
 *
 * Sends notifications when a student is enrolled in a class.
 */
class SendStudentEnrolledNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(StudentEnrolled $event): void
    {
        $enrollment = $event->enrollment;
        $student = $enrollment->student;
        $class = $enrollment->class;

        try {
            // Notify student
            if ($student && $student->user) {
                Notification::create([
                    'user_id' => $student->user->id,
                    'type' => 'success',
                    'title' => 'Enrollment Confirmed',
                    'message' => "You have been enrolled in {$class->name}. Welcome!",
                    'data' => [
                        'enrollment_id' => $enrollment->id,
                        'class_id' => $class->id,
                        'class_name' => $class->name,
                        'start_date' => $class->start_date->toIso8601String(),
                    ],
                    'action_url' => "/classes/{$class->id}",
                    'read' => false,
                ]);
            }

            // Notify teacher
            if ($class->teacher && $class->teacher->user) {
                Notification::create([
                    'user_id' => $class->teacher->user->id,
                    'type' => 'info',
                    'title' => 'New Student Enrolled',
                    'message' => "{$student->full_name} has been enrolled in your class {$class->name}.",
                    'data' => [
                        'enrollment_id' => $enrollment->id,
                        'student_id' => $student->id,
                        'student_name' => $student->full_name,
                        'class_name' => $class->name,
                    ],
                    'action_url' => "/classes/{$class->id}/students",
                    'read' => false,
                ]);
            }

            Log::info("Student enrolled notifications sent for enrollment {$enrollment->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send student enrolled notifications: " . $e->getMessage());
        }
    }
}

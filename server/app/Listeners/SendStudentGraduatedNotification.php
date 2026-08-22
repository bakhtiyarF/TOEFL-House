<?php

namespace App\Listeners;

use App\Events\StudentGraduated;
use App\Modules\Iam\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Send Student Graduated Notification Listener
 *
 * Sends notifications when a student graduates.
 */
class SendStudentGraduatedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(StudentGraduated $event): void
    {
        $student = $event->student;

        try {
            // Notify student
            if ($student->user) {
                Notification::create([
                    'user_id' => $student->user->id,
                    'type' => 'success',
                    'title' => 'Congratulations! You Graduated!',
                    'message' => "Congratulations on graduating from {$event->programName}! Your hard work has paid off.",
                    'data' => [
                        'student_id' => $student->id,
                        'student_name' => $student->full_name,
                        'program_name' => $event->programName,
                        'graduation_date' => $event->graduationDate ?? now()->toDateString(),
                    ],
                    'action_url' => "/students/{$student->id}",
                    'read' => false,
                ]);
            }

            // Notify administrators and teachers
            $admins = $student->branch->users()
                ->whereHas('roles', function ($query) {
                    $query->whereIn('name', ['admin', 'academic_coordinator', 'teacher']);
                })
                ->get();

            foreach ($admins as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'type' => 'success',
                    'title' => 'Student Graduated',
                    'message' => "{$student->full_name} has graduated from {$event->programName}.",
                    'data' => [
                        'student_id' => $student->id,
                        'student_name' => $student->full_name,
                        'program_name' => $event->programName,
                        'graduation_date' => $event->graduationDate ?? now()->toDateString(),
                    ],
                    'action_url' => "/students/{$student->id}",
                    'read' => false,
                ]);
            }

            Log::info("Student graduated notifications sent", [
                'student_id' => $student->id,
                'program_name' => $event->programName,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send student graduated notifications", [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

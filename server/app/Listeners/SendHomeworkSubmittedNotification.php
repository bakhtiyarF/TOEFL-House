<?php

namespace App\Listeners;

use App\Events\HomeworkSubmitted;
use App\Modules\Iam\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Send Homework Submitted Notification Listener
 *
 * Sends notifications when homework is submitted.
 */
class SendHomeworkSubmittedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(HomeworkSubmitted $event): void
    {
        $submission = $event->submission;
        $homework = $submission->homework;
        $student = $submission->student;
        $teacher = $homework->class->teacher;

        try {
            // Notify teacher
            if ($teacher && $teacher->user) {
                Notification::create([
                    'user_id' => $teacher->user->id,
                    'type' => 'info',
                    'title' => 'Homework Submitted',
                    'message' => "{$student->full_name} has submitted homework for {$homework->title}.",
                    'data' => [
                        'submission_id' => $submission->id,
                        'homework_id' => $homework->id,
                        'student_name' => $student->full_name,
                        'homework_title' => $homework->title,
                    ],
                    'action_url' => "/homework/{$homework->id}/submissions/{$submission->id}",
                    'read' => false,
                ]);
            }

            Log::info("Homework submitted notification sent for submission {$submission->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send homework submitted notification: " . $e->getMessage());
        }
    }
}

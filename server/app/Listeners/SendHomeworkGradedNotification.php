<?php

namespace App\Listeners;

use App\Events\HomeworkGraded;
use App\Modules\Iam\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Send Homework Graded Notification Listener
 *
 * Sends notifications when homework is graded.
 */
class SendHomeworkGradedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(HomeworkGraded $event): void
    {
        $submission = $event->submission;
        $student = $submission->student;
        $homework = $submission->homework;

        try {
            // Notify student
            if ($student && $student->user) {
                $message = "Your homework '{$homework->title}' has been graded. Grade: " . number_format($event->grade, 2);
                
                if ($event->feedback) {
                    $message .= ". Feedback: {$event->feedback}";
                }

                Notification::create([
                    'user_id' => $student->user->id,
                    'type' => 'info',
                    'title' => 'Homework Graded',
                    'message' => $message,
                    'data' => [
                        'submission_id' => $submission->id,
                        'homework_id' => $homework->id,
                        'homework_title' => $homework->title,
                        'grade' => $event->grade,
                        'feedback' => $event->feedback,
                    ],
                    'action_url' => "/homework/{$homework->id}/submissions/{$submission->id}",
                    'read' => false,
                ]);
            }

            Log::info("Homework graded notifications sent", [
                'submission_id' => $submission->id,
                'grade' => $event->grade,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send homework graded notifications", [
                'submission_id' => $submission->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

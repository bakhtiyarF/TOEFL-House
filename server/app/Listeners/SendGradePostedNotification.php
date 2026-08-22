<?php

namespace App\Listeners;

use App\Events\GradePosted;
use App\Modules\Iam\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Send Grade Posted Notification Listener
 *
 * Sends notifications when a grade is posted.
 */
class SendGradePostedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(GradePosted $event): void
    {
        $grade = $event->grade;
        $student = $grade->student;
        $exam = $grade->exam;

        try {
            // Notify student
            if ($student && $student->user) {
                Notification::create([
                    'user_id' => $student->user->id,
                    'type' => 'info',
                    'title' => 'Grade Posted',
                    'message' => "Your grade for {$exam->title} has been posted: {$grade->score}/{$exam->max_score} ({$grade->percentage}%).",
                    'data' => [
                        'grade_id' => $grade->id,
                        'exam_id' => $exam->id,
                        'exam_title' => $exam->title,
                        'score' => $grade->score,
                        'max_score' => $exam->max_score,
                        'percentage' => $grade->percentage,
                    ],
                    'action_url' => "/exams/{$exam->id}/grades",
                    'read' => false,
                ]);
            }

            Log::info("Grade posted notification sent for grade {$grade->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send grade posted notification: " . $e->getMessage());
        }
    }
}

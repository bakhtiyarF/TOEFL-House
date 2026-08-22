<?php

namespace App\Listeners;

use App\Events\GradePosted;
use App\Modules\PlatformServices\Models\Notification;
use App\Services\NotificationPreferenceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Send Grade Notification Listener
 *
 * Sends notifications when a grade is posted.
 */
class SendGradeNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private NotificationPreferenceService $notificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(GradePosted $event): void
    {
        $grade = $event->grade;
        $student = $grade->student;

        // Check if notification should be sent
        if ($student->user && !$this->notificationService->shouldNotify($student->user, 'in_app', 'grade_posted')) {
            return;
        }

        try {
            Notification::create([
                'type' => 'grade_posted',
                'title' => 'New Grade Posted',
                'message' => "Your grade for {$grade->exam->name} has been posted: {$grade->score}/{$grade->max_score} ({$grade->percentage}%)",
                'user_id' => $student->user_id,
                'branch_id' => $student->branch_id,
                'data' => [
                    'student_id' => $student->id,
                    'grade_id' => $grade->id,
                    'exam_id' => $grade->exam_id,
                    'score' => $grade->score,
                    'max_score' => $grade->max_score,
                    'percentage' => $grade->percentage,
                    'grade_letter' => $grade->grade_letter,
                ],
            ]);

            Log::info("Grade notification sent to student {$student->student_code}");
        } catch (\Exception $e) {
            Log::error("Failed to send grade notification: " . $e->getMessage());
        }
    }
}

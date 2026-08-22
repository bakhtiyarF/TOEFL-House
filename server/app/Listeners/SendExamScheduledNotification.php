<?php

namespace App\Listeners;

use App\Events\ExamScheduled;
use App\Modules\Iam\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Send Exam Scheduled Notification Listener
 *
 * Sends notifications when an exam is scheduled.
 */
class SendExamScheduledNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ExamScheduled $event): void
    {
        $exam = $event->exam;
        $class = $exam->class;

        try {
            // Notify all students in the class
            foreach ($class->students as $student) {
                if ($student->user) {
                    Notification::create([
                        'user_id' => $student->user->id,
                        'type' => 'info',
                        'title' => 'Exam Scheduled',
                        'message' => "An exam for {$class->name} has been scheduled for {$exam->exam_date->format('M j, Y')} at {$exam->start_time}.",
                        'data' => [
                            'exam_id' => $exam->id,
                            'class_name' => $class->name,
                            'exam_date' => $exam->exam_date->toIso8601String(),
                            'start_time' => $exam->start_time,
                        ],
                        'action_url' => "/exams/{$exam->id}",
                        'read' => false,
                    ]);
                }
            }

            Log::info("Exam scheduled notifications sent for exam {$exam->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send exam scheduled notifications: " . $e->getMessage());
        }
    }
}

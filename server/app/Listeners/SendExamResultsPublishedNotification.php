<?php

namespace App\Listeners;

use App\Events\ExamResultsPublished;
use App\Modules\Iam\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Send Exam Results Published Notification Listener
 *
 * Sends notifications when exam results are published.
 */
class SendExamResultsPublishedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ExamResultsPublished $event): void
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
                        'title' => 'Exam Results Published',
                        'message' => "Results for {$exam->title} have been published. Class average: " . number_format($event->averageScore, 2) . "%.",
                        'data' => [
                            'exam_id' => $exam->id,
                            'exam_title' => $exam->title,
                            'total_students' => $event->totalStudents,
                            'average_score' => $event->averageScore,
                        ],
                        'action_url' => "/exams/{$exam->id}/results",
                        'read' => false,
                    ]);
                }
            }

            // Notify teacher
            if ($class->teacher && $class->teacher->user) {
                Notification::create([
                    'user_id' => $class->teacher->user->id,
                    'type' => 'success',
                    'title' => 'Exam Results Published',
                    'message' => "Results for {$exam->title} have been published. {$event->totalStudents} students graded with average score of " . number_format($event->averageScore, 2) . "%.",
                    'data' => [
                        'exam_id' => $exam->id,
                        'exam_title' => $exam->title,
                        'total_students' => $event->totalStudents,
                        'average_score' => $event->averageScore,
                    ],
                    'action_url' => "/exams/{$exam->id}/results",
                    'read' => false,
                ]);
            }

            Log::info("Exam results published notifications sent", [
                'exam_id' => $exam->id,
                'total_students' => $event->totalStudents,
                'average_score' => $event->averageScore,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send exam results published notifications", [
                'exam_id' => $exam->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

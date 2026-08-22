<?php

namespace App\Jobs;

use App\Modules\Academic\Models\Exam;
use App\Modules\Iam\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Send Exam Reminders Job
 *
 * Sends reminders to students about upcoming exams.
 */
class SendExamReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [10, 30, 60];

    public function __construct() {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Get exams scheduled for tomorrow
            $tomorrow = now()->addDay()->format('Y-m-d');
            $exams = Exam::where('exam_date', $tomorrow)
                ->where('status', 'scheduled')
                ->with(['class.students.user', 'class.teacher.user'])
                ->get();

            $sent = 0;
            $failed = 0;

            foreach ($exams as $exam) {
                // Notify students
                foreach ($exam->class->students as $student) {
                    if ($student->user) {
                        try {
                            Notification::create([
                                'user_id' => $student->user->id,
                                'type' => 'warning',
                                'title' => 'Exam Tomorrow',
                                'message' => "Reminder: You have a {$exam->exam_type} exam for {$exam->class->name} tomorrow at {$exam->start_time}.",
                                'data' => [
                                    'exam_id' => $exam->id,
                                    'class_name' => $exam->class->name,
                                    'exam_date' => $exam->exam_date->toIso8601String(),
                                    'start_time' => $exam->start_time,
                                ],
                                'action_url' => "/exams/{$exam->id}",
                                'read' => false,
                            ]);
                            $sent++;
                        } catch (\Exception $e) {
                            $failed++;
                            Log::warning("Failed to send exam reminder to student {$student->id}: " . $e->getMessage());
                        }
                    }
                }

                // Notify teacher
                if ($exam->class->teacher && $exam->class->teacher->user) {
                    try {
                        Notification::create([
                            'user_id' => $exam->class->teacher->user->id,
                            'type' => 'info',
                            'title' => 'Exam Tomorrow',
                            'message' => "Reminder: You have a {$exam->exam_type} exam for {$exam->class->name} tomorrow at {$exam->start_time}.",
                            'data' => [
                                'exam_id' => $exam->id,
                                'class_name' => $exam->class->name,
                                'exam_date' => $exam->exam_date->toIso8601String(),
                                'start_time' => $exam->start_time,
                            ],
                            'action_url' => "/exams/{$exam->id}",
                            'read' => false,
                        ]);
                        $sent++;
                    } catch (\Exception $e) {
                        $failed++;
                        Log::warning("Failed to send exam reminder to teacher: " . $e->getMessage());
                    }
                }
            }

            Log::info("Exam reminders sent: {$sent} sent, {$failed} failed");
        } catch (\Exception $e) {
            Log::error("Failed to send exam reminders: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Exam reminders job failed permanently: " . $exception->getMessage());
    }
}

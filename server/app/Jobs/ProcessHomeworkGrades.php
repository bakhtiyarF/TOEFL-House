<?php

namespace App\Jobs;

use App\Modules\Academic\Models\Homework;
use App\Modules\Iam\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Process Homework Grades Job
 *
 * Processes and notifies about graded homework submissions.
 */
class ProcessHomeworkGrades implements ShouldQueue
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
            // Get recently graded homework submissions
            $submissions = \App\Modules\Academic\Models\HomeworkSubmission::whereNotNull('grade')
                ->whereNotNull('graded_at')
                ->where('notified', false)
                ->with(['homework.class.teacher', 'student.user'])
                ->get();

            $sent = 0;
            $failed = 0;

            foreach ($submissions as $submission) {
                $student = $submission->student;
                $homework = $submission->homework;

                if ($student && $student->user) {
                    try {
                        Notification::create([
                            'user_id' => $student->user->id,
                            'type' => 'info',
                            'title' => 'Homework Graded',
                            'message' => "Your homework '{$homework->title}' has been graded. Score: {$submission->grade}/{$homework->max_points}.",
                            'data' => [
                                'submission_id' => $submission->id,
                                'homework_id' => $homework->id,
                                'homework_title' => $homework->title,
                                'grade' => $submission->grade,
                                'max_points' => $homework->max_points,
                                'feedback' => $submission->feedback,
                            ],
                            'action_url' => "/homework/{$homework->id}/submissions/{$submission->id}",
                            'read' => false,
                        ]);

                        // Mark as notified
                        $submission->update(['notified' => true]);
                        $sent++;
                    } catch (\Exception $e) {
                        $failed++;
                        Log::warning("Failed to send grade notification to student {$student->id}: " . $e->getMessage());
                    }
                }
            }

            Log::info("Homework grade notifications processed: {$sent} sent, {$failed} failed");
        } catch (\Exception $e) {
            Log::error("Failed to process homework grades: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Homework grades processing job failed permanently: " . $exception->getMessage());
    }
}

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
 * Send Assignment Deadline Reminders Job
 *
 * Sends reminders to students about upcoming assignment deadlines.
 */
class SendAssignmentDeadlineReminders implements ShouldQueue
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
            // Get assignments due in the next 3 days
            $deadlineDate = now()->addDays(3)->format('Y-m-d');
            $assignments = Homework::where('due_date', $deadlineDate)
                ->where('status', 'active')
                ->whereDoesntHave('submissions', function ($query) {
                    $query->whereNotNull('submitted_at');
                })
                ->with(['class.students.user', 'class.teacher'])
                ->get();

            $sent = 0;
            $failed = 0;

            foreach ($assignments as $assignment) {
                foreach ($assignment->class->students as $student) {
                    if ($student->user && $student->status === 'active') {
                        try {
                            $daysUntilDeadline = now()->diffInDays($assignment->due_date, false);

                            Notification::create([
                                'user_id' => $student->user->id,
                                'type' => $daysUntilDeadline <= 1 ? 'error' : 'warning',
                                'title' => 'Assignment Deadline Reminder',
                                'message' => "Assignment '{$assignment->title}' for {$assignment->class->name} is due in {$daysUntilDeadline} days.",
                                'data' => [
                                    'assignment_id' => $assignment->id,
                                    'assignment_title' => $assignment->title,
                                    'class_name' => $assignment->class->name,
                                    'due_date' => $assignment->due_date->toIso8601String(),
                                    'days_until_deadline' => $daysUntilDeadline,
                                ],
                                'action_url' => "/assignments/{$assignment->id}",
                                'read' => false,
                            ]);
                            $sent++;
                        } catch (\Exception $e) {
                            $failed++;
                            Log::warning("Failed to send assignment deadline reminder to student {$student->id}: " . $e->getMessage());
                        }
                    }
                }
            }

            Log::info("Assignment deadline reminders sent: {$sent} sent, {$failed} failed");
        } catch (\Exception $e) {
            Log::error("Failed to send assignment deadline reminders: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Assignment deadline reminders job failed permanently: " . $exception->getMessage());
    }
}

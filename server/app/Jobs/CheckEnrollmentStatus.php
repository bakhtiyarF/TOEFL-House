<?php

namespace App\Jobs;

use App\Modules\Academic\Models\Enrollment;
use App\Modules\Iam\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Check Enrollment Status Job
 *
 * Checks enrollment statuses and sends notifications for upcoming milestones.
 */
class CheckEnrollmentStatus implements ShouldQueue
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
            // Check enrollments nearing completion
            $enrollments = Enrollment::where('status', 'active')
                ->whereNotNull('end_date')
                ->where('end_date', '<=', now()->addDays(7))
                ->where('end_date', '>=', now())
                ->with(['student.user', 'class.branch'])
                ->get();

            $sent = 0;
            $failed = 0;

            foreach ($enrollments as $enrollment) {
                $student = $enrollment->student;
                $daysUntilEnd = now()->diffInDays($enrollment->end_date, false);

                if ($student && $student->user) {
                    try {
                        Notification::create([
                            'user_id' => $student->user->id,
                            'type' => 'info',
                            'title' => 'Enrollment Ending Soon',
                            'message' => "Your enrollment in {$enrollment->class->name} will end in {$daysUntilEnd} days.",
                            'data' => [
                                'enrollment_id' => $enrollment->id,
                                'class_name' => $enrollment->class->name,
                                'end_date' => $enrollment->end_date->toIso8601String(),
                                'days_until_end' => $daysUntilEnd,
                            ],
                            'action_url' => "/enrollments/{$enrollment->id}",
                            'read' => false,
                        ]);
                        $sent++;
                    } catch (\Exception $e) {
                        $failed++;
                        Log::warning("Failed to send enrollment status notification to student {$student->id}: " . $e->getMessage());
                    }
                }
            }

            // Check for expired enrollments
            $expiredEnrollments = Enrollment::where('status', 'active')
                ->whereNotNull('end_date')
                ->where('end_date', '<', now())
                ->get();

            foreach ($expiredEnrollments as $enrollment) {
                $enrollment->update(['status' => 'completed']);
                Log::info("Enrollment {$enrollment->id} marked as completed (end date passed)");
            }

            Log::info("Enrollment status check completed: {$sent} notifications sent, {$failed} failed, " . count($expiredEnrollments) . " enrollments completed");
        } catch (\Exception $e) {
            Log::error("Failed to check enrollment status: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Enrollment status check job failed permanently: " . $exception->getMessage());
    }
}

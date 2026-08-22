<?php

namespace App\Jobs;

use App\Modules\Academic\Models\Session;
use App\Modules\Iam\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Send Attendance Reminders Job
 *
 * Sends reminders to students about upcoming sessions.
 */
class SendAttendanceReminders implements ShouldQueue
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
            // Get sessions scheduled for tomorrow
            $tomorrow = now()->addDay()->format('Y-m-d');
            $sessions = Session::where('session_date', $tomorrow)
                ->where('status', 'scheduled')
                ->with(['class.students', 'class.branch'])
                ->get();

            $sent = 0;
            $failed = 0;

            foreach ($sessions as $session) {
                foreach ($session->class->students as $student) {
                    if ($student->user && $student->status === 'active') {
                        try {
                            Notification::create([
                                'user_id' => $student->user->id,
                                'type' => 'info',
                                'title' => 'Class Reminder',
                                'message' => "Reminder: You have a {$session->class->name} class tomorrow at {$session->start_time}.",
                                'data' => [
                                    'session_id' => $session->id,
                                    'class_id' => $session->class->id,
                                    'class_name' => $session->class->name,
                                    'session_date' => $session->session_date,
                                    'start_time' => $session->start_time,
                                ],
                                'action_url' => "/sessions/{$session->id}",
                                'read' => false,
                            ]);
                            $sent++;
                        } catch (\Exception $e) {
                            $failed++;
                            Log::warning("Failed to send attendance reminder to student {$student->id}: " . $e->getMessage());
                        }
                    }
                }
            }

            Log::info("Attendance reminders sent: {$sent} sent, {$failed} failed");
        } catch (\Exception $e) {
            Log::error("Failed to send attendance reminders: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Attendance reminders job failed permanently: " . $exception->getMessage());
    }
}

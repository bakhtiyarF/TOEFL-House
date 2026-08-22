<?php

namespace App\Jobs;

use App\Modules\PeopleHr\Models\Teacher;
use App\Modules\Iam\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Send Teacher Performance Reports Job
 *
 * Sends monthly performance reports to teachers.
 */
class SendTeacherPerformanceReports implements ShouldQueue
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
            $lastMonth = now()->subMonth();
            $monthStart = $lastMonth->startOfMonth();
            $monthEnd = $lastMonth->endOfMonth();

            // Get all active teachers
            $teachers = Teacher::where('status', 'active')
                ->with(['user', 'classes.sessions.rosters', 'classes.students'])
                ->get();

            $sent = 0;
            $failed = 0;

            foreach ($teachers as $teacher) {
                if (!$teacher->user) {
                    continue;
                }

                try {
                    // Calculate teaching hours
                    $totalSessions = $teacher->classes->flatMap(function ($class) use ($monthStart, $monthEnd) {
                        return $class->sessions->whereBetween('session_date', [$monthStart, $monthEnd]);
                    })->count();

                    // Calculate average attendance rate
                    $totalAttendanceRecords = 0;
                    $totalPresent = 0;

                    $teacher->classes->each(function ($class) use ($monthStart, $monthEnd, &$totalAttendanceRecords, &$totalPresent) {
                        $class->sessions->whereBetween('session_date', [$monthStart, $monthEnd])->each(function ($session) use (&$totalAttendanceRecords, &$totalPresent) {
                            $totalAttendanceRecords += $session->rosters->count();
                            $totalPresent += $session->rosters->where('attendance_status', 'present')->count();
                        });
                    });

                    $averageAttendanceRate = $totalAttendanceRecords > 0 
                        ? ($totalPresent / $totalAttendanceRecords) * 100 
                        : 0;

                    // Calculate total students
                    $totalStudents = $teacher->classes->flatMap(function ($class) {
                        return $class->students;
                    })->unique('id')->count();

                    // Send performance report
                    Notification::create([
                        'user_id' => $teacher->user->id,
                        'type' => 'info',
                        'title' => 'Monthly Performance Report',
                        'message' => "Your performance report for {$lastMonth->format('F Y')} is ready.",
                        'data' => [
                            'teacher_id' => $teacher->id,
                            'teacher_name' => $teacher->full_name,
                            'month' => $lastMonth->format('F Y'),
                            'total_sessions' => $totalSessions,
                            'average_attendance_rate' => round($averageAttendanceRate, 1),
                            'total_students' => $totalStudents,
                        ],
                        'action_url' => "/teachers/{$teacher->id}/performance",
                        'read' => false,
                    ]);
                    $sent++;
                } catch (\Exception $e) {
                    $failed++;
                    Log::warning("Failed to send performance report to teacher {$teacher->id}: " . $e->getMessage());
                }
            }

            Log::info("Teacher performance reports sent: {$sent} sent, {$failed} failed");
        } catch (\Exception $e) {
            Log::error("Failed to send teacher performance reports: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Teacher performance reports job failed permanently: " . $exception->getMessage());
    }
}

<?php

namespace App\Jobs;

use App\Modules\Academic\Models\AcademicClass;
use App\Modules\Iam\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Generate Weekly Attendance Reports Job
 *
 * Generates weekly attendance reports for all active classes.
 */
class GenerateWeeklyAttendanceReports implements ShouldQueue
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
            // Get all active classes
            $classes = AcademicClass::where('status', 'active')
                ->with(['teacher.user', 'branch.users' => function ($query) {
                    $query->whereHas('roles', function ($q) {
                        $q->whereIn('name', ['admin', 'academic_coordinator']);
                    });
                }])
                ->get();

            $sent = 0;
            $failed = 0;

            foreach ($classes as $class) {
                try {
                    // Calculate weekly attendance
                    $weekStart = now()->startOfWeek();
                    $weekEnd = now()->endOfWeek();

                    $sessions = $class->sessions()
                        ->whereBetween('session_date', [$weekStart, $weekEnd])
                        ->with('rosters')
                        ->get();

                    $totalSessions = $sessions->count();
                    $totalAttendanceRecords = 0;
                    $totalPresent = 0;

                    foreach ($sessions as $session) {
                        $totalAttendanceRecords += $session->rosters->count();
                        $totalPresent += $session->rosters->where('attendance_status', 'present')->count();
                    }

                    $attendanceRate = $totalAttendanceRecords > 0 
                        ? ($totalPresent / $totalAttendanceRecords) * 100 
                        : 0;

                    // Notify teacher
                    if ($class->teacher && $class->teacher->user) {
                        Notification::create([
                            'user_id' => $class->teacher->user->id,
                            'type' => $attendanceRate >= 80 ? 'success' : ($attendanceRate >= 60 ? 'warning' : 'error'),
                            'title' => 'Weekly Attendance Report',
                            'message' => "Weekly attendance for {$class->name}: " . round($attendanceRate, 1) . "% ({$totalPresent}/{$totalAttendanceRecords}).",
                            'data' => [
                                'class_id' => $class->id,
                                'class_name' => $class->name,
                                'week_start' => $weekStart->toIso8601String(),
                                'week_end' => $weekEnd->toIso8601String(),
                                'total_sessions' => $totalSessions,
                                'total_present' => $totalPresent,
                                'attendance_rate' => round($attendanceRate, 1),
                            ],
                            'action_url' => "/classes/{$class->id}/attendance",
                            'read' => false,
                        ]);
                        $sent++;
                    }

                    // Notify academic coordinators if attendance is low
                    if ($attendanceRate < 70) {
                        foreach ($class->branch->users as $admin) {
                            Notification::create([
                                'user_id' => $admin->id,
                                'type' => 'warning',
                                'title' => 'Low Attendance Alert',
                                'message' => "Class {$class->name} has low attendance this week: " . round($attendanceRate, 1) . "%.",
                                'data' => [
                                    'class_id' => $class->id,
                                    'class_name' => $class->name,
                                    'teacher_name' => $class->teacher->full_name,
                                    'attendance_rate' => round($attendanceRate, 1),
                                ],
                                'action_url' => "/classes/{$class->id}/attendance",
                                'read' => false,
                            ]);
                            $sent++;
                        }
                    }
                } catch (\Exception $e) {
                    $failed++;
                    Log::warning("Failed to generate attendance report for class {$class->id}: " . $e->getMessage());
                }
            }

            Log::info("Weekly attendance reports generated: {$sent} notifications sent, {$failed} failed");
        } catch (\Exception $e) {
            Log::error("Failed to generate weekly attendance reports: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Weekly attendance reports job failed permanently: " . $exception->getMessage());
    }
}

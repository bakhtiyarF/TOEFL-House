<?php

namespace App\Jobs;

use App\Modules\Academic\Models\Enrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Calculate Student Progress Job
 *
 * Calculates progress metrics for all active enrollments.
 */
class CalculateStudentProgress implements ShouldQueue
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
            // Get all active enrollments
            $enrollments = Enrollment::where('status', 'active')
                ->with(['student', 'class.sessions.rosters', 'class.assignments.submissions'])
                ->get();

            $processed = 0;
            $failed = 0;

            foreach ($enrollments as $enrollment) {
                try {
                    $student = $enrollment->student;
                    $class = $enrollment->class;

                    // Calculate attendance rate
                    $totalSessions = $class->sessions->count();
                    $attendedSessions = $class->sessions->flatMap(function ($session) use ($student) {
                        return $session->rosters->where('student_id', $student->id);
                    })->where('attendance_status', 'present')->count();

                    $attendanceRate = $totalSessions > 0 ? ($attendedSessions / $totalSessions) * 100 : 0;

                    // Calculate assignment completion rate
                    $totalAssignments = $class->assignments->count();
                    $completedAssignments = $class->assignments->flatMap(function ($assignment) use ($student) {
                        return $assignment->submissions->where('student_id', $student->id)->whereNotNull('submitted_at');
                    })->count();

                    $assignmentCompletionRate = $totalAssignments > 0 ? ($completedAssignments / $totalAssignments) * 100 : 0;

                    // Calculate average grade
                    $grades = $student->grades()
                        ->whereHas('exam', function ($query) use ($class) {
                            $query->where('class_id', $class->id);
                        })
                        ->get();

                    $averageGrade = $grades->count() > 0 ? $grades->avg('percentage') : 0;

                    // Calculate overall progress
                    $overallProgress = ($attendanceRate * 0.3) + ($assignmentCompletionRate * 0.3) + ($averageGrade * 0.4);

                    // Update enrollment progress
                    $enrollment->update([
                        'attendance_rate' => round($attendanceRate, 2),
                        'assignment_completion_rate' => round($assignmentCompletionRate, 2),
                        'average_grade' => round($averageGrade, 2),
                        'overall_progress' => round($overallProgress, 2),
                        'last_progress_calculation' => now(),
                    ]);

                    $processed++;
                } catch (\Exception $e) {
                    $failed++;
                    Log::warning("Failed to calculate progress for enrollment {$enrollment->id}: " . $e->getMessage());
                }
            }

            Log::info("Student progress calculated: {$processed} processed, {$failed} failed");
        } catch (\Exception $e) {
            Log::error("Failed to calculate student progress: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Student progress calculation job failed permanently: " . $exception->getMessage());
    }
}

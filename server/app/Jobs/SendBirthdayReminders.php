<?php

namespace App\Jobs;

use App\Modules\Academic\Models\Student;
use App\Modules\PeopleHr\Models\Teacher;
use App\Modules\Iam\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Send Birthday Reminders Job
 *
 * Sends birthday reminders for students and teachers.
 */
class SendBirthdayReminders implements ShouldQueue
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
            $today = now()->format('m-d');
            $tomorrow = now()->addDay()->format('m-d');

            // Get students with birthdays today or tomorrow
            $students = Student::whereRaw('DATE_FORMAT(date_of_birth, "%m-%d") IN (?, ?)', [$today, $tomorrow])
                ->where('status', 'active')
                ->with(['branch.users' => function ($query) {
                    $query->whereHas('roles', function ($q) {
                        $q->whereIn('name', ['admin', 'teacher', 'student']);
                    });
                }])
                ->get();

            // Get teachers with birthdays today or tomorrow
            $teachers = Teacher::whereRaw('DATE_FORMAT(date_of_birth, "%m-%d") IN (?, ?)', [$today, $tomorrow])
                ->where('status', 'active')
                ->with(['branch.users' => function ($query) {
                    $query->whereHas('roles', function ($q) {
                        $q->whereIn('name', ['admin', 'hr_manager', 'teacher']);
                    });
                }])
                ->get();

            $sent = 0;
            $failed = 0;

            // Send student birthday notifications
            foreach ($students as $student) {
                $isToday = $student->date_of_birth->format('m-d') === $today;
                
                foreach ($student->branch->users as $user) {
                    try {
                        Notification::create([
                            'user_id' => $user->id,
                            'type' => 'info',
                            'title' => $isToday ? 'Birthday Today!' : 'Birthday Tomorrow',
                            'message' => $isToday 
                                ? "🎉 {$student->full_name} has a birthday today!"
                                : "🎂 {$student->full_name} has a birthday tomorrow.",
                            'data' => [
                                'student_id' => $student->id,
                                'student_name' => $student->full_name,
                                'birthday_date' => $student->date_of_birth->toIso8601String(),
                                'is_today' => $isToday,
                            ],
                            'action_url' => "/students/{$student->id}",
                            'read' => false,
                        ]);
                        $sent++;
                    } catch (\Exception $e) {
                        $failed++;
                        Log::warning("Failed to send birthday reminder to user {$user->id}: " . $e->getMessage());
                    }
                }
            }

            // Send teacher birthday notifications
            foreach ($teachers as $teacher) {
                $isToday = $teacher->date_of_birth->format('m-d') === $today;
                
                foreach ($teacher->branch->users as $user) {
                    try {
                        Notification::create([
                            'user_id' => $user->id,
                            'type' => 'info',
                            'title' => $isToday ? 'Birthday Today!' : 'Birthday Tomorrow',
                            'message' => $isToday 
                                ? "🎉 {$teacher->full_name} has a birthday today!"
                                : "🎂 {$teacher->full_name} has a birthday tomorrow.",
                            'data' => [
                                'teacher_id' => $teacher->id,
                                'teacher_name' => $teacher->full_name,
                                'birthday_date' => $teacher->date_of_birth->toIso8601String(),
                                'is_today' => $isToday,
                            ],
                            'action_url' => "/teachers/{$teacher->id}",
                            'read' => false,
                        ]);
                        $sent++;
                    } catch (\Exception $e) {
                        $failed++;
                        Log::warning("Failed to send birthday reminder to user {$user->id}: " . $e->getMessage());
                    }
                }
            }

            Log::info("Birthday reminders sent: {$sent} sent, {$failed} failed");
        } catch (\Exception $e) {
            Log::error("Failed to send birthday reminders: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Birthday reminders job failed permanently: " . $exception->getMessage());
    }
}

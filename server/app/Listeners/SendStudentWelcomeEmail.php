<?php

namespace App\Listeners;

use App\Events\StudentRegistered;
use App\Mail\StudentWelcomeMail;
use App\Services\NotificationPreferenceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * Send Student Welcome Email Listener
 *
 * Sends welcome email when a new student is registered.
 */
class SendStudentWelcomeEmail implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private NotificationPreferenceService $notificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(StudentRegistered $event): void
    {
        $student = $event->student;

        // Check if student has email
        if (!$student->email) {
            Log::info("Student {$student->student_code} has no email address, skipping welcome email");
            return;
        }

        // Check notification preferences (if student has user account)
        if ($student->user && !$this->notificationService->shouldNotify($student->user, 'email', 'student_registered')) {
            Log::info("Student {$student->student_code} has disabled welcome emails");
            return;
        }

        try {
            Mail::to($student->email)->send(new StudentWelcomeMail($student));
            Log::info("Welcome email sent to student {$student->student_code}");
        } catch (\Exception $e) {
            Log::error("Failed to send welcome email to student {$student->student_code}: " . $e->getMessage());
        }
    }
}

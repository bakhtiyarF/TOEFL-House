<?php

namespace App\Listeners;

use App\Events\CertificateIssued;
use App\Mail\CertificateIssuedMail;
use App\Modules\PlatformServices\Models\Notification;
use App\Services\NotificationPreferenceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Send Certificate Notification Listener
 *
 * Sends notifications and email when a certificate is issued.
 */
class SendCertificateNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private NotificationPreferenceService $notificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(CertificateIssued $event): void
    {
        $certificate = $event->certificate;
        $student = $certificate->student;

        // Send in-app notification
        if (!$student->user || $this->notificationService->shouldNotify($student->user, 'in_app', 'certificate_issued')) {
            try {
                Notification::create([
                    'type' => 'certificate_issued',
                    'title' => 'Certificate Issued',
                    'message' => "Congratulations! Your {$certificate->certificate_type} certificate has been issued.",
                    'user_id' => $student->user_id,
                    'branch_id' => $student->branch_id,
                    'data' => [
                        'student_id' => $student->id,
                        'certificate_id' => $certificate->id,
                        'certificate_number' => $certificate->certificate_number,
                        'certificate_type' => $certificate->certificate_type,
                    ],
                ]);

                Log::info("Certificate notification sent to student {$student->student_code}");
            } catch (\Exception $e) {
                Log::error("Failed to send certificate notification: " . $e->getMessage());
            }
        }

        // Send email notification
        if ($student->email && (!$student->user || $this->notificationService->shouldNotify($student->user, 'email', 'certificate_issued'))) {
            try {
                Mail::to($student->email)->send(new CertificateIssuedMail($certificate));
                Log::info("Certificate email sent to student {$student->student_code}");
            } catch (\Exception $e) {
                Log::error("Failed to send certificate email: " . $e->getMessage());
            }
        }

        // Create journey event
        try {
            $student->journeyEvents()->create([
                'event_type' => 'CERTIFICATE_ISSUED',
                'occurred_at' => now(),
                'payload' => [
                    'certificate_id' => $certificate->id,
                    'certificate_number' => $certificate->certificate_number,
                    'certificate_type' => $certificate->certificate_type,
                ],
                'actor_user_id' => $event->issuedBy,
                'actor_name' => $event->issuedBy ? 'System User' : 'System',
            ]);

            Log::info("Journey event created: CERTIFICATE_ISSUED for student {$student->student_code}");
        } catch (\Exception $e) {
            Log::error("Failed to create certificate journey event: " . $e->getMessage());
        }
    }
}

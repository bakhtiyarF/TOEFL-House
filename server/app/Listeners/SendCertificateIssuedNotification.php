<?php

namespace App\Listeners;

use App\Events\CertificateIssued;
use App\Modules\Iam\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Send Certificate Issued Notification Listener
 *
 * Sends notifications when a certificate is issued.
 */
class SendCertificateIssuedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(CertificateIssued $event): void
    {
        $certificate = $event->certificate;
        $student = $certificate->student;

        try {
            // Notify student
            if ($student && $student->user) {
                Notification::create([
                    'user_id' => $student->user->id,
                    'type' => 'success',
                    'title' => 'Certificate Issued',
                    'message' => "Congratulations! Your certificate for {$certificate->program->name} has been issued.",
                    'data' => [
                        'certificate_id' => $certificate->id,
                        'certificate_number' => $certificate->certificate_number,
                        'program_name' => $certificate->program->name,
                    ],
                    'action_url' => "/certificates/{$certificate->id}",
                    'read' => false,
                ]);
            }

            Log::info("Certificate issued notification sent for certificate {$certificate->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send certificate issued notification: " . $e->getMessage());
        }
    }
}

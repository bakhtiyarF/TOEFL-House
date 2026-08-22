<?php

namespace App\Listeners;

use App\Events\CertificateRevoked;
use App\Modules\Iam\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Send Certificate Revoked Notification Listener
 *
 * Sends notifications when a certificate is revoked.
 */
class SendCertificateRevokedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(CertificateRevoked $event): void
    {
        $certificate = $event->certificate;
        $student = $certificate->student;

        try {
            // Notify student
            if ($student && $student->user) {
                Notification::create([
                    'user_id' => $student->user->id,
                    'type' => 'error',
                    'title' => 'Certificate Revoked',
                    'message' => "Your certificate #{$certificate->certificate_number} for {$certificate->program->name} has been revoked. Reason: {$event->reason}",
                    'data' => [
                        'certificate_id' => $certificate->id,
                        'certificate_number' => $certificate->certificate_number,
                        'program_name' => $certificate->program->name,
                        'reason' => $event->reason,
                    ],
                    'action_url' => "/certificates/{$certificate->id}",
                    'read' => false,
                ]);
            }

            // Notify administrators
            $admins = $certificate->branch->users()
                ->whereHas('roles', function ($query) {
                    $query->whereIn('name', ['admin', 'academic_coordinator']);
                })
                ->get();

            foreach ($admins as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'type' => 'warning',
                    'title' => 'Certificate Revoked',
                    'message' => "Certificate #{$certificate->certificate_number} for {$student->full_name} has been revoked. Reason: {$event->reason}",
                    'data' => [
                        'certificate_id' => $certificate->id,
                        'certificate_number' => $certificate->certificate_number,
                        'student_name' => $student->full_name,
                        'reason' => $event->reason,
                        'revoked_by' => $event->revokedBy,
                    ],
                    'action_url' => "/certificates/{$certificate->id}",
                    'read' => false,
                ]);
            }

            Log::info("Certificate revoked notifications sent", [
                'certificate_id' => $certificate->id,
                'reason' => $event->reason,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send certificate revoked notifications", [
                'certificate_id' => $certificate->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

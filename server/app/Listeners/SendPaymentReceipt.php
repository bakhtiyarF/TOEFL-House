<?php

namespace App\Listeners;

use App\Events\PaymentReceived;
use App\Mail\PaymentReceiptMail;
use App\Services\NotificationPreferenceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * Send Payment Receipt Listener
 *
 * Sends payment receipt email when a payment is received.
 */
class SendPaymentReceipt implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private NotificationPreferenceService $notificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(PaymentReceived $event): void
    {
        $payment = $event->payment;
        $student = $payment->student;

        // Check if student has email
        if (!$student || !$student->email) {
            Log::info("Payment {$payment->receipt_number} student has no email address, skipping receipt");
            return;
        }

        // Check notification preferences
        if ($student->user && !$this->notificationService->shouldNotify($student->user, 'email', 'payment_received')) {
            Log::info("Student {$student->student_code} has disabled payment receipt emails");
            return;
        }

        try {
            Mail::to($student->email)->send(new PaymentReceiptMail($payment));
            Log::info("Payment receipt sent for {$payment->receipt_number}");
        } catch (\Exception $e) {
            Log::error("Failed to send payment receipt for {$payment->receipt_number}: " . $e->getMessage());
        }
    }
}

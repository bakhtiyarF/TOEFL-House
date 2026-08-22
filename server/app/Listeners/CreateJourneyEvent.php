<?php

namespace App\Listeners;

use App\Events\StudentRegistered;
use App\Events\PaymentReceived;
use App\Events\EnrollmentCreated;
use App\Modules\Academic\Models\StudentJourneyEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Create Journey Event Listener
 *
 * Creates student journey events for tracking student lifecycle.
 */
class CreateJourneyEvent implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle student registered event.
     */
    public function handleStudentRegistered(StudentRegistered $event): void
    {
        try {
            StudentJourneyEvent::create([
                'student_id' => $event->student->id,
                'event_type' => 'STUDENT_REGISTERED',
                'occurred_at' => now(),
                'payload' => [
                    'student_code' => $event->student->student_code,
                    'full_name' => $event->student->full_name,
                ],
                'actor_user_id' => $event->registeredBy,
                'actor_name' => $event->registeredBy ? 'System User' : 'System',
            ]);

            Log::info("Journey event created: STUDENT_REGISTERED for {$event->student->student_code}");
        } catch (\Exception $e) {
            Log::error("Failed to create journey event for student registration: " . $e->getMessage());
        }
    }

    /**
     * Handle payment received event.
     */
    public function handlePaymentReceived(PaymentReceived $event): void
    {
        try {
            $payment = $event->payment;

            StudentJourneyEvent::create([
                'student_id' => $payment->student_id,
                'event_type' => 'PAYMENT_RECORDED',
                'occurred_at' => now(),
                'payload' => [
                    'payment_id' => $payment->id,
                    'receipt_number' => $payment->receipt_number,
                    'amount' => $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'category' => $payment->category,
                ],
                'actor_user_id' => $event->processedBy,
                'actor_name' => $event->processedBy ? 'System User' : 'System',
            ]);

            Log::info("Journey event created: PAYMENT_RECORDED for payment {$payment->receipt_number}");
        } catch (\Exception $e) {
            Log::error("Failed to create journey event for payment: " . $e->getMessage());
        }
    }

    /**
     * Handle enrollment created event.
     */
    public function handleEnrollmentCreated(EnrollmentCreated $event): void
    {
        try {
            $enrollment = $event->enrollment;

            StudentJourneyEvent::create([
                'student_id' => $enrollment->student_id,
                'event_type' => 'ENROLLMENT_CREATED',
                'occurred_at' => now(),
                'payload' => [
                    'enrollment_id' => $enrollment->id,
                    'program_id' => $enrollment->program_id,
                    'class_id' => $enrollment->class_id,
                    'enrollment_type' => $enrollment->enrollment_type,
                    'fee_snapshot' => $enrollment->fee_snapshot_json,
                ],
                'actor_user_id' => $event->enrolledBy,
                'actor_name' => $event->enrolledBy ? 'System User' : 'System',
            ]);

            Log::info("Journey event created: ENROLLMENT_CREATED for enrollment {$enrollment->id}");
        } catch (\Exception $e) {
            Log::error("Failed to create journey event for enrollment: " . $e->getMessage());
        }
    }
}

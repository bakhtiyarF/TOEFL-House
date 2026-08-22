<?php

namespace App\Jobs;

use App\Modules\Academic\Models\Enrollment;
use App\Modules\Iam\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Send Payment Due Reminders Job
 *
 * Sends reminders to students about upcoming payment due dates.
 */
class SendPaymentDueReminders implements ShouldQueue
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

    public function __construct(
        private int $daysBeforeDue = 7
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Get enrollments with payments due in the specified number of days
            $dueDate = now()->addDays($this->daysBeforeDue)->format('Y-m-d');
            $enrollments = Enrollment::whereHas('payments', function ($query) use ($dueDate) {
                    $query->where('status', 'pending')
                        ->where('due_date', $dueDate);
                })
                ->where('status', 'active')
                ->with(['student.user', 'payments' => function ($query) use ($dueDate) {
                    $query->where('status', 'pending')
                        ->where('due_date', $dueDate);
                }])
                ->get();

            $sent = 0;
            $failed = 0;

            foreach ($enrollments as $enrollment) {
                $student = $enrollment->student;
                
                if ($student && $student->user) {
                    foreach ($enrollment->payments as $payment) {
                        try {
                            $daysUntilDue = now()->diffInDays($payment->due_date, false);
                            
                            Notification::create([
                                'user_id' => $student->user->id,
                                'type' => $daysUntilDue <= 3 ? 'warning' : 'info',
                                'title' => 'Payment Reminder',
                                'message' => "Your payment of {$payment->amount} AFN is due in {$daysUntilDue} days.",
                                'data' => [
                                    'payment_id' => $payment->id,
                                    'enrollment_id' => $enrollment->id,
                                    'amount' => $payment->amount,
                                    'due_date' => $payment->due_date,
                                    'days_until_due' => $daysUntilDue,
                                ],
                                'action_url' => "/payments/{$payment->id}",
                                'read' => false,
                            ]);
                            $sent++;
                        } catch (\Exception $e) {
                            $failed++;
                            Log::warning("Failed to send payment reminder to student {$student->id}: " . $e->getMessage());
                        }
                    }
                }
            }

            Log::info("Payment due reminders sent: {$sent} sent, {$failed} failed");
        } catch (\Exception $e) {
            Log::error("Failed to send payment due reminders: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Payment due reminders job failed permanently: " . $exception->getMessage());
    }
}

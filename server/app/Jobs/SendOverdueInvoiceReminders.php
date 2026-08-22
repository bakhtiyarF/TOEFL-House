<?php

namespace App\Jobs;

use App\Modules\FinancePayroll\Models\Invoice;
use App\Modules\Iam\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Send Overdue Invoice Reminders Job
 *
 * Sends reminders to students about overdue invoices.
 */
class SendOverdueInvoiceReminders implements ShouldQueue
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
            // Get overdue invoices
            $overdueInvoices = Invoice::where('status', 'issued')
                ->where('due_date', '<', now())
                ->whereHas('payments', function ($query) {
                    $query->where('status', 'completed');
                }, '=', 0) // No completed payments
                ->orWhereRaw('(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE invoice_id = invoices.id AND status = "completed") < invoices.total_amount')
                ->with(['student.user', 'branch'])
                ->get();

            $sent = 0;
            $failed = 0;

            foreach ($overdueInvoices as $invoice) {
                $student = $invoice->student;
                
                if ($student && $student->user) {
                    try {
                        $daysOverdue = now()->diffInDays($invoice->due_date, false);
                        $paid = $invoice->payments()->where('status', 'completed')->sum('amount');
                        $remaining = $invoice->total_amount - $paid;

                        Notification::create([
                            'user_id' => $student->user->id,
                            'type' => $daysOverdue > 30 ? 'error' : 'warning',
                            'title' => 'Invoice Overdue',
                            'message' => "Your invoice #{$invoice->invoice_number} is {$daysOverdue} days overdue. Outstanding balance: {$remaining} AFN.",
                            'data' => [
                                'invoice_id' => $invoice->id,
                                'invoice_number' => $invoice->invoice_number,
                                'days_overdue' => $daysOverdue,
                                'remaining_balance' => $remaining,
                            ],
                            'action_url' => "/invoices/{$invoice->id}",
                            'read' => false,
                        ]);
                        $sent++;
                    } catch (\Exception $e) {
                        $failed++;
                        Log::warning("Failed to send overdue reminder to student {$student->id}: " . $e->getMessage());
                    }
                }

                // Update invoice status to overdue
                if ($invoice->status === 'issued') {
                    $invoice->update(['status' => 'overdue']);
                }
            }

            Log::info("Overdue invoice reminders sent: {$sent} sent, {$failed} failed");
        } catch (\Exception $e) {
            Log::error("Failed to send overdue invoice reminders: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Overdue invoice reminders job failed permanently: " . $exception->getMessage());
    }
}

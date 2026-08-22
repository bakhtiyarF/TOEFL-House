<?php

namespace App\Listeners;

use App\Events\InvoiceOverdue;
use App\Modules\Iam\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Send Invoice Overdue Notification Listener
 *
 * Sends notifications when an invoice becomes overdue.
 */
class SendInvoiceOverdueNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(InvoiceOverdue $event): void
    {
        $invoice = $event->invoice;
        $student = $invoice->student;

        try {
            // Notify student
            if ($student && $student->user) {
                Notification::create([
                    'user_id' => $student->user->id,
                    'type' => $event->daysOverdue > 30 ? 'error' : 'warning',
                    'title' => 'Invoice Overdue',
                    'message' => "Your invoice #{$invoice->invoice_number} is {$event->daysOverdue} days overdue. Please make payment as soon as possible.",
                    'data' => [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'amount' => $invoice->total_amount,
                        'days_overdue' => $event->daysOverdue,
                        'due_date' => $invoice->due_date->toIso8601String(),
                    ],
                    'action_url' => "/invoices/{$invoice->id}",
                    'read' => false,
                ]);
            }

            // Notify finance administrators
            $admins = $invoice->branch->users()
                ->whereHas('roles', function ($query) {
                    $query->whereIn('name', ['admin', 'finance_manager']);
                })
                ->get();

            foreach ($admins as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'type' => $event->daysOverdue > 30 ? 'error' : 'warning',
                    'title' => 'Invoice Overdue',
                    'message' => "Invoice #{$invoice->invoice_number} for {$student->full_name} is {$event->daysOverdue} days overdue.",
                    'data' => [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'student_name' => $student->full_name,
                        'amount' => $invoice->total_amount,
                        'days_overdue' => $event->daysOverdue,
                    ],
                    'action_url' => "/invoices/{$invoice->id}",
                    'read' => false,
                ]);
            }

            Log::info("Invoice overdue notifications sent", [
                'invoice_id' => $invoice->id,
                'days_overdue' => $event->daysOverdue,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send invoice overdue notifications", [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

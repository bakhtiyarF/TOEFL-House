<?php

namespace App\Listeners;

use App\Events\InvoiceCreated;
use App\Modules\Iam\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Send Invoice Created Notification Listener
 *
 * Sends notifications when an invoice is created.
 */
class SendInvoiceCreatedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(InvoiceCreated $event): void
    {
        $invoice = $event->invoice;
        $student = $invoice->student;

        try {
            // Notify student
            if ($student && $student->user) {
                Notification::create([
                    'user_id' => $student->user->id,
                    'type' => 'info',
                    'title' => 'New Invoice',
                    'message' => "A new invoice for {$invoice->total_amount} AFN has been created. Due date: {$invoice->due_date->format('M j, Y')}.",
                    'data' => [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'total_amount' => $invoice->total_amount,
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
                    'type' => 'info',
                    'title' => 'Invoice Created',
                    'message' => "Invoice #{$invoice->invoice_number} for {$student->full_name} has been created.",
                    'data' => [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'student_name' => $student->full_name,
                        'total_amount' => $invoice->total_amount,
                    ],
                    'action_url' => "/invoices/{$invoice->id}",
                    'read' => false,
                ]);
            }

            Log::info("Invoice created notifications sent for invoice {$invoice->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send invoice created notifications: " . $e->getMessage());
        }
    }
}

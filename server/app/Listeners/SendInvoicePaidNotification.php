<?php

namespace App\Listeners;

use App\Events\InvoicePaid;
use App\Modules\Iam\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Send Invoice Paid Notification Listener
 *
 * Sends notifications when an invoice is fully paid.
 */
class SendInvoicePaidNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(InvoicePaid $event): void
    {
        $invoice = $event->invoice;
        $student = $invoice->student;

        try {
            // Notify student
            if ($student && $student->user) {
                Notification::create([
                    'user_id' => $student->user->id,
                    'type' => 'success',
                    'title' => 'Invoice Paid',
                    'message' => "Your invoice #{$invoice->invoice_number} has been fully paid. Thank you!",
                    'data' => [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'total_amount' => $invoice->total_amount,
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
                    'type' => 'success',
                    'title' => 'Invoice Paid',
                    'message' => "Invoice #{$invoice->invoice_number} for {$student->full_name} has been fully paid.",
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

            Log::info("Invoice paid notifications sent for invoice {$invoice->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send invoice paid notifications: " . $e->getMessage());
        }
    }
}

<?php

namespace App\Events;

use App\Modules\FinancePayroll\Models\Invoice;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Invoice Paid Event
 *
 * Fired when an invoice is fully paid.
 */
class InvoicePaid
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public ?string $paidBy = null
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('invoices'),
            new PrivateChannel('student.' . $this->invoice->student_id),
            new PrivateChannel('finance'),
        ];
    }
}

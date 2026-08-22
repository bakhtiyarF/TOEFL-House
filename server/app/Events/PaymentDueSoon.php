<?php

namespace App\Events;

use App\Modules\Academic\Models\Student;
use App\Modules\FinancePayroll\Models\Payment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Payment Due Soon Event
 *
 * Fired when a payment is due soon.
 */
class PaymentDueSoon
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Student $student,
        public Payment $payment,
        public int $daysUntilDue
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('student.' . $this->student->id),
            new PrivateChannel('payment.' . $this->payment->id),
        ];
    }
}

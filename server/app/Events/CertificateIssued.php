<?php

namespace App\Events;

use App\Modules\Academic\Models\Certificate;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Certificate Issued Event
 *
 * Fired when a certificate is issued to a student.
 */
class CertificateIssued
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Certificate $certificate,
        public ?string $issuedBy = null
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('certificates'),
            new PrivateChannel('student.' . $this->certificate->student_id),
        ];
    }
}

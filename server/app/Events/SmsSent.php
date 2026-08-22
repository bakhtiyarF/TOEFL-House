<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * SMS Sent Event
 *
 * Fired when an SMS is sent.
 */
class SmsSent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $to,
        public string $message,
        public ?string $sid = null,
        public ?string $status = null,
        public ?string $template = null,
        public array $templateData = []
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('sms'),
        ];
    }
}

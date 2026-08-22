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
 * SMS Failed Event
 *
 * Fired when an SMS fails to send.
 */
class SmsFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $to,
        public string $message,
        public string $error,
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

<?php

namespace App\Events;

use App\Modules\FundingImpact\Models\Donation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Donation Received Event
 *
 * Fired when a donation is received.
 */
class DonationReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Donation $donation,
        public ?string $receivedBy = null
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('donations'),
            new PrivateChannel('campaign.' . $this->donation->campaign_id),
            new PrivateChannel('funding'),
        ];
    }
}

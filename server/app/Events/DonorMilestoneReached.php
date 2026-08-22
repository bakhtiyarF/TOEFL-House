<?php

namespace App\Events;

use App\Modules\FundingImpact\Models\Donor;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Donor Milestone Reached Event
 *
 * Fired when a donor reaches a donation milestone.
 */
class DonorMilestoneReached
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Donor $donor,
        public float $totalDonations,
        public string $milestone
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('donors'),
            new PrivateChannel('donor.' . $this->donor->id),
        ];
    }
}

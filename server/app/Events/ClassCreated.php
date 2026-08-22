<?php

namespace App\Events;

use App\Modules\Academic\Models\AcademicClass;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class Created Event
 *
 * Fired when a new class is created.
 */
class ClassCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public AcademicClass $class,
        public ?string $createdBy = null
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('classes'),
        ];
    }
}

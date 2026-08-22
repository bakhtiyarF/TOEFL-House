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
 * Class Updated Event
 *
 * Fired when a class is updated.
 */
class ClassUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public AcademicClass $class,
        public ?string $updatedBy = null
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('classes'),
            new PrivateChannel('class.' . $this->class->id),
        ];
    }
}

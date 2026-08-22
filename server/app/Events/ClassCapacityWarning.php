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
 * Class Capacity Warning Event
 *
 * Fired when a class is approaching or has reached capacity.
 */
class ClassCapacityWarning
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public AcademicClass $class,
        public int $currentEnrollment,
        public int $capacity,
        public string $warningLevel // 'approaching' or 'full'
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

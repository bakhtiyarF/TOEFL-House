<?php

namespace App\Events;

use App\Modules\Academic\Models\AcademicClass;
use App\Modules\Academic\Models\Student;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class Starting Soon Event
 *
 * Fired when a class is starting soon.
 */
class ClassStartingSoon
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Student $student,
        public AcademicClass $class,
        public int $hoursUntilStart
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('student.' . $this->student->id),
            new PrivateChannel('class.' . $this->class->id),
        ];
    }
}

<?php

namespace App\Events;

use App\Modules\Academic\Models\Homework;
use App\Modules\Academic\Models\Student;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Homework Due Soon Event
 *
 * Fired when homework is due soon.
 */
class HomeworkDueSoon
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Student $student,
        public Homework $homework,
        public int $hoursUntilDue
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('student.' . $this->student->id),
            new PrivateChannel('homework.' . $this->homework->id),
        ];
    }
}

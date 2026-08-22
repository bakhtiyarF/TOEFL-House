<?php

namespace App\Events;

use App\Modules\Academic\Models\Grade;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Grade Posted Event
 *
 * Fired when a grade is posted for a student.
 */
class GradePosted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Grade $grade,
        public ?string $postedBy = null
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('grades'),
            new PrivateChannel('student.' . $this->grade->student_id),
            new PrivateChannel('class.' . $this->grade->class_id),
        ];
    }
}

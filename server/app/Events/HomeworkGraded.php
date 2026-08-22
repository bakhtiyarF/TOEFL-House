<?php

namespace App\Events;

use App\Modules\Academic\Models\HomeworkSubmission;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Homework Graded Event
 *
 * Fired when homework is graded.
 */
class HomeworkGraded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public HomeworkSubmission $submission,
        public float $grade,
        public ?string $feedback = null
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('homework'),
            new PrivateChannel('student.' . $this->submission->student_id),
        ];
    }
}

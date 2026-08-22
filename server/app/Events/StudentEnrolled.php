<?php

namespace App\Events;

use App\Modules\Academic\Models\Enrollment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Student Enrolled Event
 *
 * Fired when a student is enrolled in a class.
 */
class StudentEnrolled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Enrollment $enrollment,
        public ?string $enrolledBy = null
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('enrollments'),
            new PrivateChannel('student.' . $this->enrollment->student_id),
            new PrivateChannel('class.' . $this->enrollment->class_id),
        ];
    }
}

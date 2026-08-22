<?php

namespace App\Events;

use App\Modules\Academic\Models\Roster;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Attendance Recorded Event
 *
 * Fired when attendance is recorded for a session.
 */
class AttendanceRecorded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Roster $roster,
        public ?string $recordedBy = null
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('attendance'),
            new PrivateChannel('student.' . $this->roster->student_id),
            new PrivateChannel('session.' . $this->roster->session_id),
        ];
    }
}

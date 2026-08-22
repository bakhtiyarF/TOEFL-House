<?php

namespace App\Events;

use App\Modules\PeopleHr\Models\Teacher;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Teacher Updated Event
 *
 * Fired when a teacher is updated.
 */
class TeacherUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Teacher $teacher,
        public ?string $updatedBy = null
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('teachers'),
            new PrivateChannel('teacher.' . $this->teacher->id),
        ];
    }
}

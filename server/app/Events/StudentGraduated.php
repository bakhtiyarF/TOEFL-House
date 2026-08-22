<?php

namespace App\Events;

use App\Modules\Academic\Models\Student;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Student Graduated Event
 *
 * Fired when a student graduates from a program.
 */
class StudentGraduated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Student $student,
        public string $programName,
        public ?string $graduationDate = null
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('students'),
            new PrivateChannel('student.' . $this->student->id),
        ];
    }
}

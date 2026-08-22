<?php

namespace App\Events;

use App\Modules\Academic\Models\Exam;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Exam Scheduled Event
 *
 * Fired when an exam is scheduled.
 */
class ExamScheduled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Exam $exam,
        public ?string $scheduledBy = null
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('exams'),
            new PrivateChannel('class.' . $this->exam->class_id),
        ];
    }
}

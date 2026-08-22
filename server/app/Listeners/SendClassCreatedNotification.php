<?php

namespace App\Listeners;

use App\Events\ClassCreated;
use App\Modules\Iam\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Send Class Created Notification Listener
 *
 * Sends notifications when a new class is created.
 */
class SendClassCreatedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ClassCreated $event): void
    {
        $class = $event->class;

        try {
            // Notify all teachers in the branch
            $teachers = $class->branch->teachers()->active()->get();

            foreach ($teachers as $teacher) {
                if ($teacher->user) {
                    Notification::create([
                        'user_id' => $teacher->user->id,
                        'type' => 'info',
                        'title' => 'New Class Created',
                        'message' => "A new class '{$class->name}' has been created in your branch.",
                        'data' => [
                            'class_id' => $class->id,
                            'class_name' => $class->name,
                            'program' => $class->program?->name,
                        ],
                        'action_url' => "/classes/{$class->id}",
                        'read' => false,
                    ]);
                }
            }

            Log::info("Class created notifications sent for class {$class->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send class created notifications: " . $e->getMessage());
        }
    }
}

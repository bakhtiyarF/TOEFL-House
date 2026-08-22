<?php

namespace App\Listeners;

use App\Events\ClassCapacityWarning;
use App\Modules\Iam\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Send Class Capacity Warning Notification Listener
 *
 * Sends notifications when a class is approaching or has reached capacity.
 */
class SendClassCapacityWarningNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ClassCapacityWarning $event): void
    {
        $class = $event->class;

        try {
            $message = $event->warningLevel === 'full'
                ? "Class {$class->name} has reached full capacity ({$event->currentEnrollment}/{$event->capacity})."
                : "Class {$class->name} is approaching capacity ({$event->currentEnrollment}/{$event->capacity}).";

            // Notify teacher
            if ($class->teacher && $class->teacher->user) {
                Notification::create([
                    'user_id' => $class->teacher->user->id,
                    'type' => $event->warningLevel === 'full' ? 'error' : 'warning',
                    'title' => 'Class Capacity ' . ucfirst($event->warningLevel),
                    'message' => $message,
                    'data' => [
                        'class_id' => $class->id,
                        'class_name' => $class->name,
                        'current_enrollment' => $event->currentEnrollment,
                        'capacity' => $event->capacity,
                        'warning_level' => $event->warningLevel,
                    ],
                    'action_url' => "/classes/{$class->id}",
                    'read' => false,
                ]);
            }

            // Notify academic coordinators
            $admins = $class->branch->users()
                ->whereHas('roles', function ($query) {
                    $query->whereIn('name', ['admin', 'academic_coordinator']);
                })
                ->get();

            foreach ($admins as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'type' => $event->warningLevel === 'full' ? 'error' : 'warning',
                    'title' => 'Class Capacity ' . ucfirst($event->warningLevel),
                    'message' => $message,
                    'data' => [
                        'class_id' => $class->id,
                        'class_name' => $class->name,
                        'teacher_name' => $class->teacher->full_name ?? 'Unassigned',
                        'current_enrollment' => $event->currentEnrollment,
                        'capacity' => $event->capacity,
                        'warning_level' => $event->warningLevel,
                    ],
                    'action_url' => "/classes/{$class->id}",
                    'read' => false,
                ]);
            }

            Log::info("Class capacity warning notifications sent", [
                'class_id' => $class->id,
                'warning_level' => $event->warningLevel,
                'current_enrollment' => $event->currentEnrollment,
                'capacity' => $event->capacity,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send class capacity warning notifications", [
                'class_id' => $class->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

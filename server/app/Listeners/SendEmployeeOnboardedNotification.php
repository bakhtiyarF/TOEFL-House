<?php

namespace App\Listeners;

use App\Events\EmployeeOnboarded;
use App\Modules\Iam\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Send Employee Onboarded Notification Listener
 *
 * Sends notifications when a new employee is onboarded.
 */
class SendEmployeeOnboardedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(EmployeeOnboarded $event): void
    {
        $employee = $event->employee;

        try {
            // Notify employee if they have a user account
            if ($employee->user) {
                Notification::create([
                    'user_id' => $employee->user->id,
                    'type' => 'success',
                    'title' => 'Welcome to the Team!',
                    'message' => "Welcome {$employee->full_name}! You have been successfully onboarded.",
                    'data' => [
                        'employee_id' => $employee->id,
                        'employee_name' => $employee->full_name,
                        'position' => $employee->position ?? 'Staff',
                        'department' => $employee->department ?? 'General',
                    ],
                    'action_url' => "/employees/{$employee->id}",
                    'read' => false,
                ]);
            }

            // Notify HR administrators
            $admins = $employee->branch->users()
                ->whereHas('roles', function ($query) {
                    $query->whereIn('name', ['admin', 'hr_manager']);
                })
                ->get();

            foreach ($admins as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'type' => 'success',
                    'title' => 'New Employee Onboarded',
                    'message' => "{$employee->full_name} has been onboarded as {$employee->position ?? 'Staff'}.",
                    'data' => [
                        'employee_id' => $employee->id,
                        'employee_name' => $employee->full_name,
                        'position' => $employee->position ?? 'Staff',
                        'department' => $employee->department ?? 'General',
                        'onboarded_by' => $event->onboardedBy,
                    ],
                    'action_url' => "/employees/{$employee->id}",
                    'read' => false,
                ]);
            }

            Log::info("Employee onboarded notifications sent", [
                'employee_id' => $employee->id,
                'onboarded_by' => $event->onboardedBy,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send employee onboarded notifications", [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

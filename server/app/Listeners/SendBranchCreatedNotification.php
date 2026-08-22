<?php

namespace App\Listeners;

use App\Events\BranchCreated;
use App\Modules\Iam\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Send Branch Created Notification Listener
 *
 * Sends notifications when a new branch is created.
 */
class SendBranchCreatedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(BranchCreated $event): void
    {
        $branch = $event->branch;

        try {
            // Notify organization administrators
            $admins = \App\Modules\Iam\Models\User::whereHas('roles', function ($query) {
                    $query->where('name', 'organization_admin');
                })
                ->get();

            foreach ($admins as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'type' => 'success',
                    'title' => 'New Branch Created',
                    'message' => "A new branch '{$branch->name}' has been created.",
                    'data' => [
                        'branch_id' => $branch->id,
                        'branch_name' => $branch->name,
                        'branch_code' => $branch->code,
                    ],
                    'action_url' => "/branches/{$branch->id}",
                    'read' => false,
                ]);
            }

            Log::info("Branch created notifications sent for branch {$branch->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send branch created notifications: " . $e->getMessage());
        }
    }
}

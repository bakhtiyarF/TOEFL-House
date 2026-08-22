<?php

namespace App\Listeners;

use App\Events\DonorMilestoneReached;
use App\Modules\Iam\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Send Donor Milestone Notification Listener
 *
 * Sends notifications when a donor reaches a donation milestone.
 */
class SendDonorMilestoneNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(DonorMilestoneReached $event): void
    {
        $donor = $event->donor;

        try {
            // Notify donor if they have a user account
            if ($donor->user) {
                Notification::create([
                    'user_id' => $donor->user->id,
                    'type' => 'success',
                    'title' => 'Donation Milestone Reached!',
                    'message' => "Congratulations! You've reached the {$event->milestone} milestone with total donations of " . number_format($event->totalDonations, 2) . " AFN. Thank you for your generous support!",
                    'data' => [
                        'donor_id' => $donor->id,
                        'donor_name' => $donor->full_name,
                        'milestone' => $event->milestone,
                        'total_donations' => $event->totalDonations,
                    ],
                    'action_url' => "/donors/{$donor->id}",
                    'read' => false,
                ]);
            }

            // Notify funding administrators
            $admins = $donor->branch->users()
                ->whereHas('roles', function ($query) {
                    $query->whereIn('name', ['admin', 'funding_manager']);
                })
                ->get();

            foreach ($admins as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'type' => 'success',
                    'title' => 'Donor Milestone Reached',
                    'message' => "Donor {$donor->full_name} has reached the {$event->milestone} milestone with total donations of " . number_format($event->totalDonations, 2) . " AFN.",
                    'data' => [
                        'donor_id' => $donor->id,
                        'donor_name' => $donor->full_name,
                        'milestone' => $event->milestone,
                        'total_donations' => $event->totalDonations,
                    ],
                    'action_url' => "/donors/{$donor->id}",
                    'read' => false,
                ]);
            }

            Log::info("Donor milestone notifications sent", [
                'donor_id' => $donor->id,
                'milestone' => $event->milestone,
                'total_donations' => $event->totalDonations,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send donor milestone notifications", [
                'donor_id' => $donor->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

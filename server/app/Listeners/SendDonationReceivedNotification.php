<?php

namespace App\Listeners;

use App\Events\DonationReceived;
use App\Modules\Iam\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Send Donation Received Notification Listener
 *
 * Sends notifications when a donation is received.
 */
class SendDonationReceivedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(DonationReceived $event): void
    {
        $donation = $event->donation;
        $donor = $donation->donor;

        try {
            // Notify donor if they have a user account
            if ($donor && $donor->user) {
                Notification::create([
                    'user_id' => $donor->user->id,
                    'type' => 'success',
                    'title' => 'Donation Received',
                    'message' => "Thank you for your donation of {$donation->amount} AFN!",
                    'data' => [
                        'donation_id' => $donation->id,
                        'amount' => $donation->amount,
                        'campaign_name' => $donation->campaign?->name,
                    ],
                    'action_url' => "/donations/{$donation->id}",
                    'read' => false,
                ]);
            }

            // Notify funding administrators
            $admins = $donation->branch->users()
                ->whereHas('roles', function ($query) {
                    $query->whereIn('name', ['admin', 'funding_manager']);
                })
                ->get();

            foreach ($admins as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'type' => 'success',
                    'title' => 'Donation Received',
                    'message' => "Donation of {$donation->amount} AFN received from {$donor->full_name}.",
                    'data' => [
                        'donation_id' => $donation->id,
                        'donor_name' => $donor->full_name,
                        'amount' => $donation->amount,
                        'campaign_name' => $donation->campaign?->name,
                    ],
                    'action_url' => "/donations/{$donation->id}",
                    'read' => false,
                ]);
            }

            Log::info("Donation received notifications sent for donation {$donation->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send donation received notifications: " . $e->getMessage());
        }
    }
}

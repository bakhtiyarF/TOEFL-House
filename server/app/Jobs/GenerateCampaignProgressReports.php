<?php

namespace App\Jobs;

use App\Modules\FundingImpact\Models\Campaign;
use App\Modules\Iam\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Generate Campaign Progress Reports Job
 *
 * Generates progress reports for active funding campaigns.
 */
class GenerateCampaignProgressReports implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [10, 30, 60];

    public function __construct() {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Get active campaigns
            $campaigns = Campaign::where('status', 'active')
                ->whereNotNull('goal_amount')
                ->where('goal_amount', '>', 0)
                ->with(['branch.users' => function ($query) {
                    $query->whereHas('roles', function ($q) {
                        $q->whereIn('name', ['admin', 'funding_manager']);
                    });
                }])
                ->get();

            $sent = 0;
            $failed = 0;

            foreach ($campaigns as $campaign) {
                try {
                    $totalDonations = $campaign->donations()->where('status', 'completed')->sum('amount');
                    $progressPercentage = ($totalDonations / $campaign->goal_amount) * 100;
                    $daysRemaining = $campaign->end_date ? now()->diffInDays($campaign->end_date, false) : null;

                    // Notify funding administrators
                    foreach ($campaign->branch->users as $admin) {
                        Notification::create([
                            'user_id' => $admin->id,
                            'type' => $progressPercentage >= 100 ? 'success' : ($progressPercentage >= 75 ? 'info' : 'warning'),
                            'title' => 'Campaign Progress Report',
                            'message' => "Campaign '{$campaign->name}' is at " . round($progressPercentage, 1) . "% of goal.",
                            'data' => [
                                'campaign_id' => $campaign->id,
                                'campaign_name' => $campaign->name,
                                'goal_amount' => $campaign->goal_amount,
                                'total_donations' => $totalDonations,
                                'progress_percentage' => round($progressPercentage, 1),
                                'days_remaining' => $daysRemaining,
                            ],
                            'action_url' => "/campaigns/{$campaign->id}",
                            'read' => false,
                        ]);
                        $sent++;
                    }

                    // Check if campaign goal is reached
                    if ($progressPercentage >= 100 && $campaign->status === 'active') {
                        $campaign->update(['status' => 'completed']);
                        
                        Log::info("Campaign {$campaign->id} marked as completed (goal reached)");
                    }
                } catch (\Exception $e) {
                    $failed++;
                    Log::warning("Failed to generate progress report for campaign {$campaign->id}: " . $e->getMessage());
                }
            }

            Log::info("Campaign progress reports generated: {$sent} sent, {$failed} failed");
        } catch (\Exception $e) {
            Log::error("Failed to generate campaign progress reports: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Campaign progress reports job failed permanently: " . $exception->getMessage());
    }
}

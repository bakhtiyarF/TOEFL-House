<?php

namespace App\Jobs;

use App\Modules\Iam\Models\Session;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Cleanup Expired Sessions Job
 *
 * Cleans up expired user sessions to maintain database performance.
 */
class CleanupExpiredSessions implements ShouldQueue
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
            // Delete sessions older than 30 days
            $cutoffDate = now()->subDays(30);
            
            $deleted = Session::where('last_activity', '<', $cutoffDate)->delete();

            Log::info("Expired sessions cleaned up: {$deleted} sessions deleted");
        } catch (\Exception $e) {
            Log::error("Failed to cleanup expired sessions: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Session cleanup job failed permanently: " . $exception->getMessage());
    }
}

<?php

namespace App\Jobs;

use App\Services\ReportGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Generate Monthly Reports Job
 *
 * Generates monthly reports for all branches automatically.
 */
class GenerateMonthlyReports implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [60, 180, 360];

    public function __construct(
        private string $year,
        private string $month
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ReportGenerationService $reportService): void
    {
        try {
            Log::info("Generating monthly reports for {$this->year}/{$this->month}");

            $branches = \App\Modules\Iam\Models\Branch::where('status', 'active')->get();
            
            $generated = 0;
            $failed = 0;

            foreach ($branches as $branch) {
                try {
                    $reportService->generateFinancialReport(
                        $branch->id,
                        $this->year,
                        $this->month
                    );
                    $generated++;
                    Log::info("Monthly report generated for branch {$branch->id}");
                } catch (\Exception $e) {
                    $failed++;
                    Log::error("Failed to generate monthly report for branch {$branch->id}: " . $e->getMessage());
                }
            }

            Log::info("Monthly report generation completed: {$generated} generated, {$failed} failed");

        } catch (\Exception $e) {
            Log::error("Failed to generate monthly reports: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Monthly report generation failed permanently: " . $exception->getMessage());
    }
}

<?php

namespace App\Jobs;

use App\Services\ReportGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Generate Financial Report Job
 *
 * Generates financial reports in the background.
 */
class GenerateFinancialReport implements ShouldQueue
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

    public function __construct(
        private string $branchId,
        private string $year,
        private string $month,
        private string $requestedBy
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ReportGenerationService $reportService): void
    {
        try {
            Log::info("Generating financial report for {$this->year}/{$this->month} (branch: {$this->branchId})");

            $report = $reportService->generateFinancialReport(
                $this->branchId,
                $this->year,
                $this->month
            );

            // Store report path in database or notify user
            Log::info("Financial report generated successfully: {$report['filename']}");

            // Optionally: Send notification to user who requested the report
            // Notification::send($this->requestedBy, new ReportGeneratedNotification($report));

        } catch (\Exception $e) {
            Log::error("Failed to generate financial report: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Financial report generation failed permanently: " . $exception->getMessage());

        // Optionally: Send failure notification to user
        // Notification::send($this->requestedBy, new ReportGenerationFailedNotification($exception));
    }
}

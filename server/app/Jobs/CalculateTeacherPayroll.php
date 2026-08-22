<?php

namespace App\Jobs;

use App\Modules\FinancePayroll\Services\PayrollService;
use App\Modules\PeopleHr\Models\Teacher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Calculate Teacher Payroll Job
 *
 * Calculates payroll for all teachers in a branch for a specific period.
 */
class CalculateTeacherPayroll implements ShouldQueue
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
        private string $periodKey,
        private string $requestedBy
    ) {}

    /**
     * Execute the job.
     */
    public function handle(PayrollService $payrollService): void
    {
        try {
            Log::info("Calculating payroll for period {$this->periodKey} (branch: {$this->branchId})");

            $teachers = Teacher::where('branch_id', $this->branchId)
                ->where('status', 'active')
                ->get();

            $processed = 0;
            $failed = 0;

            foreach ($teachers as $teacher) {
                try {
                    $payrollService->calculateTeacherPayroll(
                        $teacher->id,
                        $this->periodKey
                    );
                    $processed++;
                    Log::info("Payroll calculated for teacher {$teacher->id}");
                } catch (\Exception $e) {
                    $failed++;
                    Log::error("Failed to calculate payroll for teacher {$teacher->id}: " . $e->getMessage());
                }
            }

            Log::info("Payroll calculation completed: {$processed} processed, {$failed} failed");

            // Optionally: Send notification to user who requested the calculation
            // Notification::send($this->requestedBy, new PayrollCalculatedNotification($processed, $failed));

        } catch (\Exception $e) {
            Log::error("Failed to calculate payroll: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Payroll calculation failed permanently: " . $exception->getMessage());

        // Optionally: Send failure notification to user
        // Notification::send($this->requestedBy, new PayrollCalculationFailedNotification($exception));
    }
}

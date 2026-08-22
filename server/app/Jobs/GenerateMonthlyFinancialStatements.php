<?php

namespace App\Jobs;

use App\Modules\FinancePayroll\Models\Payment;
use App\Modules\Iam\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Generate Monthly Financial Statements Job
 *
 * Generates monthly financial statements for all branches.
 */
class GenerateMonthlyFinancialStatements implements ShouldQueue
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
            $lastMonth = now()->subMonth();
            $monthStart = $lastMonth->startOfMonth();
            $monthEnd = $lastMonth->endOfMonth();

            // Get all branches
            $branches = \App\Modules\Iam\Models\Branch::where('status', 'active')
                ->with(['users' => function ($query) {
                    $query->whereHas('roles', function ($q) {
                        $q->whereIn('name', ['admin', 'finance_manager']);
                    });
                }])
                ->get();

            $generated = 0;
            $failed = 0;

            foreach ($branches as $branch) {
                try {
                    // Calculate monthly revenue
                    $totalRevenue = Payment::where('branch_id', $branch->id)
                        ->where('status', 'completed')
                        ->whereBetween('payment_date', [$monthStart, $monthEnd])
                        ->sum('amount');

                    // Calculate monthly expenses (from invoices)
                    $totalExpenses = \App\Modules\FinancePayroll\Models\Invoice::where('branch_id', $branch->id)
                        ->where('status', 'paid')
                        ->whereBetween('due_date', [$monthStart, $monthEnd])
                        ->sum('total_amount');

                    // Calculate net profit
                    $netProfit = $totalRevenue - $totalExpenses;

                    // Notify finance administrators
                    foreach ($branch->users as $admin) {
                        Notification::create([
                            'user_id' => $admin->id,
                            'type' => 'info',
                            'title' => 'Monthly Financial Statement',
                            'message' => "Financial statement for {$lastMonth->format('F Y')} is ready.",
                            'data' => [
                                'branch_id' => $branch->id,
                                'branch_name' => $branch->name,
                                'month' => $lastMonth->format('F Y'),
                                'total_revenue' => $totalRevenue,
                                'total_expenses' => $totalExpenses,
                                'net_profit' => $netProfit,
                            ],
                            'action_url' => "/reports/financial?branch={$branch->id}&month={$lastMonth->format('Y-m')}",
                            'read' => false,
                        ]);
                        $generated++;
                    }
                } catch (\Exception $e) {
                    $failed++;
                    Log::warning("Failed to generate financial statement for branch {$branch->id}: " . $e->getMessage());
                }
            }

            Log::info("Monthly financial statements generated: {$generated} generated, {$failed} failed");
        } catch (\Exception $e) {
            Log::error("Failed to generate monthly financial statements: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Monthly financial statements job failed permanently: " . $exception->getMessage());
    }
}

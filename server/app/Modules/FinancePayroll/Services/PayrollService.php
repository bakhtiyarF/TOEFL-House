<?php

namespace App\Modules\FinancePayroll\Services;

use App\Modules\PlatformServices\Services\RuleEngineService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Payroll Service
 *
 * The target of every delegated call from People & HR (06 §6).
 * Implements the five salary models from 02 §8.
 */
class PayrollService
{
    public function __construct(
        private RuleEngineService $ruleEngine
    ) {}

    /**
     * Compute teacher's total due amount for a period
     * Based on their salary_type (02 §8.1)
     */
    public function computeTeacherDueAmount(string $teacherId, string $periodKey, string $branchId): array
    {
        $teacher = DB::table('teachers')->where('id', $teacherId)->first();
        if (!$teacher) {
            return ['dueAmount' => 0, 'model' => 'unknown', 'breakdown' => []];
        }

        $breakdown = match ($teacher->salary_type) {
            'fixed' => $this->computeFixed($teacher),
            'per_skill', 'per_session' => $this->computePerSkill($teacher, $branchId),
            'hybrid' => $this->computeHybrid($teacher, $branchId),
            'per_level' => $this->computePerLevel($teacher, $branchId),
            default => ['total' => 0, 'lines' => []],
        };

        return [
            'dueAmount' => $breakdown['total'],
            'model' => $teacher->salary_type,
            'breakdown' => $breakdown['lines'],
        ];
    }

    /**
     * Fixed salary: base_salary only (02 §8.1)
     */
    private function computeFixed(object $teacher): array
    {
        return [
            'total' => (float)$teacher->base_salary,
            'lines' => [['type' => 'base_salary', 'amount' => (float)$teacher->base_salary]],
        ];
    }

    /**
     * Per-skill/per-session: sum of adjusted class payroll lines (02 §8.2)
     */
    private function computePerSkill(object $teacher, string $branchId): array
    {
        $lines = DB::table('class_teacher_skills as cts')
            ->join('classes as c', 'cts.class_id', '=', 'c.id')
            ->where('cts.teacher_id', $teacher->id)
            ->where('c.status', 'active')
            ->where('c.branch_id', $branchId)
            ->select('cts.*', 'c.id as class_id', 'c.name as class_name', 'c.activation_date', 'c.start_date')
            ->get();

        $total = 0;
        $breakdown = [];

        foreach ($lines as $line) {
            // Skip if not yet active (02 §8.2, step 1)
            $activationDate = $line->activation_date ?? $line->start_date;
            if ($activationDate && $activationDate > now()->toDateString()) {
                continue;
            }

            // Count active enrolled students (02 §8.2, step 2)
            $enrolledCount = DB::table('student_semesters')
                ->where('class_id', $line->class_id)
                ->where('status', 'active')
                ->count();

            // Run payroll rules (02 §8.2, step 3 → rules 19-20)
            $ruleResult = $this->ruleEngine->evaluate('payroll', $branchId, [
                'enrolledCount' => $enrolledCount,
                'classStatus' => 'active',
            ]);

            $payrollMultiplier = $ruleResult['finalOutputs']['payrollMultiplier'] ?? 1.0;
            $payrollTier = $ruleResult['finalOutputs']['payrollTier'] ?? 'default';

            // Calculate adjusted amount (02 §8.2, step 4)
            $adjustedAmount = round((float)$line->monthly_rate * $payrollMultiplier);
            $total += $adjustedAmount;

            $breakdown[] = [
                'type' => 'class_skill',
                'class_name' => $line->class_name,
                'monthly_rate' => (float)$line->monthly_rate,
                'multiplier' => $payrollMultiplier,
                'tier' => $payrollTier,
                'enrolled' => $enrolledCount,
                'amount' => $adjustedAmount,
            ];
        }

        return ['total' => $total, 'lines' => $breakdown];
    }

    /**
     * Hybrid: base_salary + sum of adjusted class payroll lines (02 §8.1)
     */
    private function computeHybrid(object $teacher, string $branchId): array
    {
        $perSkill = $this->computePerSkill($teacher, $branchId);
        $perSkill['total'] += (float)$teacher->base_salary;
        array_unshift($perSkill['lines'], [
            'type' => 'base_salary',
            'amount' => (float)$teacher->base_salary,
        ]);
        return $perSkill;
    }

    /**
     * Per-level: sum over distinct levels × rate (02 §8.3)
     */
    private function computePerLevel(object $teacher, string $branchId): array
    {
        $assignments = DB::table('class_teacher_skills as cts')
            ->join('classes as c', 'cts.class_id', '=', 'c.id')
            ->join('levels as l', 'c.level_id', '=', 'l.id')
            ->where('cts.teacher_id', $teacher->id)
            ->where('c.status', 'active')
            ->where('c.branch_id', $branchId)
            ->select('l.id as level_id', 'l.code as level_code', 'l.name as level_name', 'c.activation_date', 'c.start_date')
            ->distinct()
            ->get();

        $total = 0;
        $breakdown = [];

        foreach ($assignments as $assignment) {
            // Apply activation-date filter (02 §8.3 harmonization)
            $activationDate = $assignment->activation_date ?? $assignment->start_date;
            if ($activationDate && $activationDate > now()->toDateString()) {
                continue;
            }

            // Count assignments at this level
            $assignmentCount = DB::table('class_teacher_skills as cts')
                ->join('classes as c', 'cts.class_id', '=', 'c.id')
                ->where('cts.teacher_id', $teacher->id)
                ->where('c.level_id', $assignment->level_id)
                ->where('c.status', 'active')
                ->count();

            // Look up rate: level-wide rate first, then average
            $rateRow = DB::table('teacher_level_skill_rates')
                ->where('teacher_id', $teacher->id)
                ->where('level_code', $assignment->level_code)
                ->whereNull('skill_id')
                ->first();

            if ($rateRow) {
                $rate = (float)$rateRow->rate_per_skill;
            } else {
                // Fallback: average monthly_rate across assignments at this level
                $avg = DB::table('class_teacher_skills as cts')
                    ->join('classes as c', 'cts.class_id', '=', 'c.id')
                    ->where('cts.teacher_id', $teacher->id)
                    ->where('c.level_id', $assignment->level_id)
                    ->where('c.status', 'active')
                    ->avg('cts.monthly_rate');
                $rate = (float)($avg ?? 0);
            }

            $lineTotal = $assignmentCount * $rate;
            $total += $lineTotal;

            $breakdown[] = [
                'type' => 'per_level',
                'level_name' => $assignment->level_name,
                'level_code' => $assignment->level_code,
                'assignment_count' => $assignmentCount,
                'rate' => $rate,
                'amount' => $lineTotal,
            ];
        }

        return ['total' => $total, 'lines' => $breakdown];
    }

    /**
     * Pay a teacher's salary — writes ledger with no-double-full-pay guard (07 §4)
     */
    public function paySalary(
        string $teacherId,
        string $periodKey,
        string $periodLabel,
        float $amount,
        string $paymentType,
        string $branchId,
        string $operatorName
    ): array {
        // Guard: at most one 'full' payment per teacher+period
        if ($paymentType === 'full') {
            $existingFull = DB::table('teacher_salary_ledger')
                ->where('teacher_id', $teacherId)
                ->where('period_key', $periodKey)
                ->where('payment_type', 'full')
                ->exists();

            if ($existingFull) {
                throw new \RuntimeException('Full payment already recorded for this period');
            }
        }

        return DB::transaction(function () use ($teacherId, $periodKey, $periodLabel, $amount, $paymentType, $branchId, $operatorName) {
            $ledgerId = Str::uuid()->toString();

            DB::table('teacher_salary_ledger')->insert([
                'id' => $ledgerId,
                'teacher_id' => $teacherId,
                'period_key' => $periodKey,
                'period_label' => $periodLabel,
                'due_amount' => 0, // computed separately
                'paid_amount' => $amount,
                'payment_type' => $paymentType,
                'branch_id' => $branchId,
                'paid_at' => now(),
                'operator_name' => $operatorName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Write matching financial transaction
            $txId = Str::uuid()->toString();
            DB::table('financial_transactions')->insert([
                'id' => $txId,
                'type' => 'expense',
                'category' => 'payroll',
                'amount' => $amount,
                'date' => now()->toDateString(),
                'description' => "Teacher salary: {$periodLabel}",
                'reference_id' => $ledgerId,
                'operator_name' => $operatorName,
                'branch_id' => $branchId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return ['ledger_id' => $ledgerId, 'transaction_id' => $txId];
        });
    }
}

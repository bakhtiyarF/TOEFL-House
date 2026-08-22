<?php

namespace App\Modules\FundingImpact\Services;

use App\Modules\FinancePayroll\Services\TuitionCalculationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Scholarship Service
 *
 * Implements the scholarship award → tuition calculation connection (11 §5).
 * On every scholarship_awards creation, automatically updates the student's
 * scholarshipPercent and recomputes their net tuition.
 */
class ScholarshipService
{
    public function __construct(
        private TuitionCalculationService $tuitionService,
    ) {}

    /**
     * Award a scholarship to a student (11 §5)
     *
     * Algorithm:
     * 1. Match (student_id, semester) against enrollments — reject if no match
     * 2. grossTuition from fee_snapshot_json
     * 3. awardPercent = min(100, (amount / grossTuition) × 100)
     * 4. newScholarshipPercent = min(100, current + awardPercent) — additive
     * 5. Re-run tuition pipeline with updated scholarshipPercent
     * 6. Persist recomputed figures on enrollment
     * 7. Increment scholarships.allocated_amount
     *
     * Budget guard: reject if total_budget would be exceeded
     */
    public function awardScholarship(
        string $scholarshipId,
        string $studentId,
        float $amount,
        ?string $semester,
        ?string $notes,
        string $branchId,
    ): array {
        return DB::transaction(function () use ($scholarshipId, $studentId, $amount, $semester, $notes, $branchId) {
            // Fetch scholarship fund
            $scholarship = DB::table('scholarships')->where('id', $scholarshipId)->first();
            if (!$scholarship) {
                throw new \RuntimeException('Scholarship fund not found');
            }

            // Budget guard (11 §10)
            if ($scholarship->allocated_amount + $amount > $scholarship->total_budget) {
                throw new \RuntimeException(
                    "Budget exceeded. Remaining: " . ($scholarship->total_budget - $scholarship->allocated_amount) . " AFN",
                    400
                );
            }

            // Step 1: Find matching enrollment (11 §5, step 1)
            $enrollment = DB::table('enrollments')
                ->where('student_id', $studentId)
                ->where('status', 'active')
                ->when($semester, fn($q) => $q->where('semester_name', $semester))
                ->first();

            if (!$enrollment) {
                throw new \RuntimeException(
                    'No matching active enrollment found for this student',
                    400
                );
            }

            // Step 2: Get gross tuition from fee snapshot
            $feeSnapshot = json_decode($enrollment->fee_snapshot_json, true);
            $grossTuition = collect($feeSnapshot['fee_rules'] ?? [])
                ->where('fee_type', 'semester')
                ->sum('amount');

            if ($grossTuition <= 0) {
                $grossTuition = 1; // prevent division by zero
            }

            // Step 3: Calculate award percent
            $awardPercent = min(100, ($amount / $grossTuition) * 100);

            // Step 4: New scholarship percent (additive)
            $currentScholarshipPercent = (float)$enrollment->scholarship_percent;
            $newScholarshipPercent = min(100, $currentScholarshipPercent + $awardPercent);

            // Step 5: Re-run tuition pipeline (02 §6)
            $result = $this->tuitionService->resolveStudentFinanceAmounts(
                grossTuition: $grossTuition,
                requestedDiscountPercent: (float)$enrollment->discount_percent,
                requestedScholarshipPercent: $newScholarshipPercent,
                amountPaid: 0, // total paid calculated separately
                branchId: $branchId,
            );

            // Step 6: Persist recomputed figures on enrollment
            DB::table('enrollments')->where('id', $enrollment->id)->update([
                'scholarship_percent' => $newScholarshipPercent,
                'updated_at' => now(),
            ]);

            // Step 7: Create award record
            $awardId = Str::uuid()->toString();
            DB::table('scholarship_awards')->insert([
                'id' => $awardId,
                'scholarship_id' => $scholarshipId,
                'student_id' => $studentId,
                'amount' => $amount,
                'award_date' => now()->toDateString(),
                'semester' => $semester,
                'notes' => $notes,
                'branch_id' => $branchId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Step 8: Increment allocated_amount (11 §4)
            DB::table('scholarships')->where('id', $scholarshipId)->increment('allocated_amount', $amount);

            return [
                'award_id' => $awardId,
                'enrollment_id' => $enrollment->id,
                'award_percent' => round($awardPercent, 2),
                'new_scholarship_percent' => round($newScholarshipPercent, 2),
                'recomputed' => $result,
                'remaining_budget' => $scholarship->total_budget - $scholarship->allocated_amount - $amount,
            ];
        });
    }
}

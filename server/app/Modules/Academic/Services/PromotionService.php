<?php

namespace App\Modules\Academic\Services;

use App\Modules\Academic\Models\PromotionRule;
use App\Modules\Academic\Models\Student;
use App\Modules\Iam\Services\BranchScopeService;
use Illuminate\Support\Collection;

/**
 * Promotion Service
 * Uses promotion_rules (score + attendance) per program version.
 * Authoritative per 05 spec.
 */
class PromotionService
{
    public function __construct(
        private BranchScopeService $branchScope
    ) {}

    public function evaluatePromotion(Student $student, string $programVersionId, ?string $branchId = null): array
    {
        $rules = $this->getPromotionRules($programVersionId, $branchId);

        $currentLevel = $student->currentEnrollment?->level_code;
        $attendanceRate = $student->attendance_rate;
        $examAvg = $this->getLatestExamAverage($student);

        $recommendations = [];

        foreach ($rules as $rule) {
            $passes = true;

            if ($rule->min_score !== null && $examAvg < $rule->min_score) $passes = false;
            if ($rule->min_attendance_pct !== null && $attendanceRate < $rule->min_attendance_pct) $passes = false;

            if ($passes) {
                $recommendations[] = [
                    'rule_id' => $rule->id,
                    'from_level' => $rule->from_level_id,
                    'to_level' => $rule->to_level_id,
                    'auto_promote' => $rule->auto_promote,
                ];
            }
        }

        return [
            'student_id' => $student->id,
            'current_level' => $currentLevel,
            'exam_avg' => $examAvg,
            'attendance_rate' => $attendanceRate,
            'recommendations' => $recommendations,
            'can_promote' => count($recommendations) > 0,
        ];
    }

    private function getPromotionRules(string $programVersionId, ?string $branchId): Collection
    {
        $query = PromotionRule::where('program_version_id', $programVersionId);

        if ($branchId) {
            $branchRules = (clone $query)->where('branch_id', $branchId)->get();
            if ($branchRules->count()) return $branchRules;
        }

        return $query->whereNull('branch_id')->get();
    }

    private function getLatestExamAverage(Student $student): float
    {
        $results = $student->examResults()->latest()->take(3)->pluck('score');
        return $results->count() ? round($results->avg(), 1) : 0;
    }
}

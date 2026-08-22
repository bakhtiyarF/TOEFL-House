<?php

namespace App\Modules\Academic\Services;

use App\Modules\Academic\Models\PlacementRule;
use App\Modules\Iam\Services\BranchScopeService;
use Illuminate\Support\Collection;

/**
 * Placement Service
 * Score-based placement rule evaluation (branch overrides first).
 */
class PlacementService
{
    public function __construct(
        private BranchScopeService $branchScope
    ) {}

    public function recommendLevel(int $score, string $programVersionId, ?string $branchId = null): ?string
    {
        $rules = $this->getApplicableRules($programVersionId, $branchId);

        foreach ($rules as $rule) {
            if ($score >= $rule->min_score && $score <= $rule->max_score) {
                return $rule->recommended_level_id;
            }
        }

        return null;
    }

    public function getApplicableRules(string $programVersionId, ?string $branchId = null): Collection
    {
        $query = PlacementRule::where('program_version_id', $programVersionId)
            ->orderBy('sort_order');

        if ($branchId) {
            // Branch-specific first, then fallback
            $branchRules = (clone $query)->where('branch_id', $branchId)->get();
            if ($branchRules->isNotEmpty()) {
                return $branchRules;
            }
            return $query->whereNull('branch_id')->get();
        }

        return $query->whereNull('branch_id')->get();
    }

    public function createPlacementRule(array $data): PlacementRule
    {
        return PlacementRule::create($data);
    }
}

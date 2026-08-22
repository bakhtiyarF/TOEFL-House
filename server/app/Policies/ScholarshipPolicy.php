<?php

namespace App\Policies;

use App\Modules\Iam\Models\User;
use App\Modules\FundingImpact\Models\Scholarship;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Scholarship Policy
 *
 * Fine-grained authorization for scholarship operations.
 */
class ScholarshipPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any scholarships
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('scholarship.view');
    }

    /**
     * Determine if user can view specific scholarship
     */
    public function view(User $user, Scholarship $scholarship): bool
    {
        if (!$user->hasPermission('scholarship.view')) {
            return false;
        }

        return $this->canAccessBranch($user, $scholarship->branch_id);
    }

    /**
     * Determine if user can create scholarships
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('scholarship.create');
    }

    /**
     * Determine if user can update scholarship
     */
    public function update(User $user, Scholarship $scholarship): bool
    {
        if (!$user->hasPermission('scholarship.update')) {
            return false;
        }

        return $this->canAccessBranch($user, $scholarship->branch_id);
    }

    /**
     * Determine if user can delete scholarship
     */
    public function delete(User $user, Scholarship $scholarship): bool
    {
        if (!$user->hasPermission('scholarship.delete')) {
            return false;
        }

        // Prevent deletion if scholarship has active awards
        if ($scholarship->active_award_count > 0) {
            return false;
        }

        return $this->canAccessBranch($user, $scholarship->branch_id);
    }

    /**
     * Determine if user can award scholarship to student
     */
    public function award(User $user, Scholarship $scholarship): bool
    {
        if (!$user->hasPermission('scholarship.award')) {
            return false;
        }

        // Check if scholarship is available
        if (!$scholarship->isAvailable()) {
            return false;
        }

        return $this->canAccessBranch($user, $scholarship->branch_id);
    }

    /**
     * Determine if user can view scholarship awards
     */
    public function viewAwards(User $user, Scholarship $scholarship): bool
    {
        if (!$user->hasPermission('scholarship.view_awards')) {
            return false;
        }

        return $this->canAccessBranch($user, $scholarship->branch_id);
    }

    /**
     * Determine if user can revoke scholarship award
     */
    public function revokeAward(User $user, Scholarship $scholarship): bool
    {
        if (!$user->hasPermission('scholarship.revoke_award')) {
            return false;
        }

        return $this->canAccessBranch($user, $scholarship->branch_id);
    }

    /**
     * Determine if user can view scholarship reports
     */
    public function viewReports(User $user): bool
    {
        return $user->hasPermission('scholarship.view_reports');
    }

    /**
     * Check if user can access branch
     */
    private function canAccessBranch(User $user, string $branchId): bool
    {
        if ($user->hasPermission('organization.manage')) {
            return true;
        }

        return $user->branches->contains('id', $branchId);
    }
}

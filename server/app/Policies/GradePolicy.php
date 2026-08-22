<?php

namespace App\Policies;

use App\Modules\Iam\Models\User;
use App\Modules\Academic\Models\Grade;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Grade Policy
 *
 * Fine-grained authorization for grade operations.
 */
class GradePolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any grades
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('grade.view');
    }

    /**
     * Determine if user can view specific grade
     */
    public function view(User $user, Grade $grade): bool
    {
        if (!$user->hasPermission('grade.view')) {
            return false;
        }

        return $this->canAccessBranch($user, $grade->branch_id);
    }

    /**
     * Determine if user can create grades
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('grade.create');
    }

    /**
     * Determine if user can update grade
     */
    public function update(User $user, Grade $grade): bool
    {
        if (!$user->hasPermission('grade.update')) {
            return false;
        }

        // Only unpublished grades can be updated
        if ($grade->is_published) {
            return false;
        }

        return $this->canAccessBranch($user, $grade->branch_id);
    }

    /**
     * Determine if user can delete grade
     */
    public function delete(User $user, Grade $grade): bool
    {
        if (!$user->hasPermission('grade.delete')) {
            return false;
        }

        // Only unpublished grades can be deleted
        if ($grade->is_published) {
            return false;
        }

        return $this->canAccessBranch($user, $grade->branch_id);
    }

    /**
     * Determine if user can publish grade
     */
    public function publish(User $user, Grade $grade): bool
    {
        if (!$user->hasPermission('grade.publish')) {
            return false;
        }

        return $this->canAccessBranch($user, $grade->branch_id);
    }

    /**
     * Determine if user can view grade reports
     */
    public function viewReports(User $user): bool
    {
        return $user->hasPermission('grade.view_reports');
    }

    /**
     * Determine if user can export grades
     */
    public function export(User $user): bool
    {
        return $user->hasPermission('grade.export');
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

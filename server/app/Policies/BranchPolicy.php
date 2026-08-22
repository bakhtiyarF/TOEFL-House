<?php

namespace App\Policies;

use App\Modules\Iam\Models\User;
use App\Modules\Iam\Models\Branch;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Branch Policy
 *
 * Fine-grained authorization for branch operations.
 */
class BranchPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any branches
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('branch.view');
    }

    /**
     * Determine if user can view specific branch
     */
    public function view(User $user, Branch $branch): bool
    {
        if (!$user->hasPermission('branch.view')) {
            return false;
        }

        return $this->canAccessBranch($user, $branch->id);
    }

    /**
     * Determine if user can create branches
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('branch.create');
    }

    /**
     * Determine if user can update branch
     */
    public function update(User $user, Branch $branch): bool
    {
        if (!$user->hasPermission('branch.update')) {
            return false;
        }

        return $this->canAccessBranch($user, $branch->id);
    }

    /**
     * Determine if user can delete branch
     */
    public function delete(User $user, Branch $branch): bool
    {
        if (!$user->hasPermission('branch.delete')) {
            return false;
        }

        // Prevent deletion if branch has active students or teachers
        if ($branch->active_student_count > 0 || $branch->active_teacher_count > 0) {
            return false;
        }

        return $this->canAccessBranch($user, $branch->id);
    }

    /**
     * Determine if user can activate branch
     */
    public function activate(User $user, Branch $branch): bool
    {
        if (!$user->hasPermission('branch.activate')) {
            return false;
        }

        return $this->canAccessBranch($user, $branch->id);
    }

    /**
     * Determine if user can deactivate branch
     */
    public function deactivate(User $user, Branch $branch): bool
    {
        if (!$user->hasPermission('branch.deactivate')) {
            return false;
        }

        return $this->canAccessBranch($user, $branch->id);
    }

    /**
     * Determine if user can view branch reports
     */
    public function viewReports(User $user, Branch $branch): bool
    {
        if (!$user->hasPermission('branch.view_reports')) {
            return false;
        }

        return $this->canAccessBranch($user, $branch->id);
    }

    /**
     * Determine if user can manage branch settings
     */
    public function manageSettings(User $user, Branch $branch): bool
    {
        if (!$user->hasPermission('branch.manage_settings')) {
            return false;
        }

        return $this->canAccessBranch($user, $branch->id);
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

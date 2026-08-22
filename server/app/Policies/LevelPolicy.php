<?php

namespace App\Policies;

use App\Modules\Iam\Models\User;
use App\Modules\Academic\Models\Level;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Level Policy
 *
 * Fine-grained authorization for level operations.
 */
class LevelPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any levels
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('level.view');
    }

    /**
     * Determine if user can view specific level
     */
    public function view(User $user, Level $level): bool
    {
        if (!$user->hasPermission('level.view')) {
            return false;
        }

        return $this->canAccessBranch($user, $level->branch_id);
    }

    /**
     * Determine if user can create levels
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('level.create');
    }

    /**
     * Determine if user can update level
     */
    public function update(User $user, Level $level): bool
    {
        if (!$user->hasPermission('level.update')) {
            return false;
        }

        return $this->canAccessBranch($user, $level->branch_id);
    }

    /**
     * Determine if user can delete level
     */
    public function delete(User $user, Level $level): bool
    {
        if (!$user->hasPermission('level.delete')) {
            return false;
        }

        // Prevent deletion if level has active enrollments
        if ($level->active_enrollment_count > 0) {
            return false;
        }

        return $this->canAccessBranch($user, $level->branch_id);
    }

    /**
     * Determine if user can manage level curriculum
     */
    public function manageCurriculum(User $user, Level $level): bool
    {
        if (!$user->hasPermission('level.manage_curriculum')) {
            return false;
        }

        return $this->canAccessBranch($user, $level->branch_id);
    }

    /**
     * Determine if user can view level reports
     */
    public function viewReports(User $user): bool
    {
        return $user->hasPermission('level.view_reports');
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

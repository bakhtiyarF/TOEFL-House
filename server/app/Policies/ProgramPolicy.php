<?php

namespace App\Policies;

use App\Modules\Iam\Models\User;
use App\Modules\Academic\Models\Program;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Program Policy
 *
 * Fine-grained authorization for program operations.
 */
class ProgramPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any programs
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('program.view');
    }

    /**
     * Determine if user can view specific program
     */
    public function view(User $user, Program $program): bool
    {
        if (!$user->hasPermission('program.view')) {
            return false;
        }

        return $this->canAccessBranch($user, $program->branch_id);
    }

    /**
     * Determine if user can create programs
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('program.create');
    }

    /**
     * Determine if user can update program
     */
    public function update(User $user, Program $program): bool
    {
        if (!$user->hasPermission('program.update')) {
            return false;
        }

        return $this->canAccessBranch($user, $program->branch_id);
    }

    /**
     * Determine if user can delete program
     */
    public function delete(User $user, Program $program): bool
    {
        if (!$user->hasPermission('program.delete')) {
            return false;
        }

        // Prevent deletion if program has active enrollments
        if ($program->active_enrollment_count > 0) {
            return false;
        }

        return $this->canAccessBranch($user, $program->branch_id);
    }

    /**
     * Determine if user can activate program
     */
    public function activate(User $user, Program $program): bool
    {
        if (!$user->hasPermission('program.activate')) {
            return false;
        }

        return $this->canAccessBranch($user, $program->branch_id);
    }

    /**
     * Determine if user can deactivate program
     */
    public function deactivate(User $user, Program $program): bool
    {
        if (!$user->hasPermission('program.deactivate')) {
            return false;
        }

        return $this->canAccessBranch($user, $program->branch_id);
    }

    /**
     * Determine if user can manage program versions
     */
    public function manageVersions(User $user, Program $program): bool
    {
        if (!$user->hasPermission('program.manage_versions')) {
            return false;
        }

        return $this->canAccessBranch($user, $program->branch_id);
    }

    /**
     * Determine if user can view program reports
     */
    public function viewReports(User $user): bool
    {
        return $user->hasPermission('program.view_reports');
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

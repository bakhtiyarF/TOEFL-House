<?php

namespace App\Policies;

use App\Modules\Iam\Models\User;
use App\Modules\Academic\Models\Subject;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Subject Policy
 *
 * Fine-grained authorization for subject operations.
 */
class SubjectPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any subjects
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('subject.view');
    }

    /**
     * Determine if user can view specific subject
     */
    public function view(User $user, Subject $subject): bool
    {
        if (!$user->hasPermission('subject.view')) {
            return false;
        }

        return $this->canAccessBranch($user, $subject->branch_id);
    }

    /**
     * Determine if user can create subjects
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('subject.create');
    }

    /**
     * Determine if user can update subject
     */
    public function update(User $user, Subject $subject): bool
    {
        if (!$user->hasPermission('subject.update')) {
            return false;
        }

        return $this->canAccessBranch($user, $subject->branch_id);
    }

    /**
     * Determine if user can delete subject
     */
    public function delete(User $user, Subject $subject): bool
    {
        if (!$user->hasPermission('subject.delete')) {
            return false;
        }

        // Prevent deletion if subject has active enrollments
        if ($subject->active_enrollment_count > 0) {
            return false;
        }

        return $this->canAccessBranch($user, $subject->branch_id);
    }

    /**
     * Determine if user can manage subject curriculum
     */
    public function manageCurriculum(User $user, Subject $subject): bool
    {
        if (!$user->hasPermission('subject.manage_curriculum')) {
            return false;
        }

        return $this->canAccessBranch($user, $subject->branch_id);
    }

    /**
     * Determine if user can view subject reports
     */
    public function viewReports(User $user): bool
    {
        return $user->hasPermission('subject.view_reports');
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

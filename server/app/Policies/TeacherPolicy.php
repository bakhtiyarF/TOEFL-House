<?php

namespace App\Policies;

use App\Modules\Iam\Models\User;
use App\Modules\PeopleHr\Models\Teacher;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Teacher Policy
 *
 * Fine-grained authorization for teacher operations.
 */
class TeacherPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any teachers
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('teacher.view');
    }

    /**
     * Determine if user can view specific teacher
     */
    public function view(User $user, Teacher $teacher): bool
    {
        if (!$user->hasPermission('teacher.view')) {
            return false;
        }

        return $this->canAccessBranch($user, $teacher->branch_id);
    }

    /**
     * Determine if user can create teachers
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('teacher.create');
    }

    /**
     * Determine if user can update teacher
     */
    public function update(User $user, Teacher $teacher): bool
    {
        if (!$user->hasPermission('teacher.update')) {
            return false;
        }

        return $this->canAccessBranch($user, $teacher->branch_id);
    }

    /**
     * Determine if user can delete teacher
     */
    public function delete(User $user, Teacher $teacher): bool
    {
        if (!$user->hasPermission('teacher.delete')) {
            return false;
        }

        // Prevent deletion if teacher has active classes
        if ($teacher->active_class_count > 0) {
            return false;
        }

        return $this->canAccessBranch($user, $teacher->branch_id);
    }

    /**
     * Determine if user can view teacher's salary information
     */
    public function viewSalary(User $user, Teacher $teacher): bool
    {
        if (!$user->hasPermission('teacher.view_salary')) {
            return false;
        }

        return $this->canAccessBranch($user, $teacher->branch_id);
    }

    /**
     * Determine if user can process teacher's payroll
     */
    public function processPayroll(User $user, Teacher $teacher): bool
    {
        if (!$user->hasPermission('payroll.process')) {
            return false;
        }

        return $this->canAccessBranch($user, $teacher->branch_id);
    }

    /**
     * Determine if user can assign classes to teacher
     */
    public function assignClasses(User $user, Teacher $teacher): bool
    {
        if (!$user->hasPermission('teacher.assign_classes')) {
            return false;
        }

        return $this->canAccessBranch($user, $teacher->branch_id);
    }

    /**
     * Determine if user can evaluate teacher
     */
    public function evaluate(User $user, Teacher $teacher): bool
    {
        if (!$user->hasPermission('teacher.evaluate')) {
            return false;
        }

        return $this->canAccessBranch($user, $teacher->branch_id);
    }

    /**
     * Determine if user can transfer teacher to another branch
     */
    public function transfer(User $user, Teacher $teacher): bool
    {
        if (!$user->hasPermission('teacher.transfer')) {
            return false;
        }

        // Must have access to both current and target branch
        return $this->canAccessBranch($user, $teacher->branch_id);
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

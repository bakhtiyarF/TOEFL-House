<?php

namespace App\Policies;

use App\Modules\Iam\Models\User;
use App\Modules\Academic\Models\Student;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Student Policy
 *
 * Fine-grained authorization for student operations.
 * Checks user permissions and branch scope.
 */
class StudentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any students
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('student.view');
    }

    /**
     * Determine if user can view specific student
     */
    public function view(User $user, Student $student): bool
    {
        if (!$user->hasPermission('student.view')) {
            return false;
        }

        return $this->canAccessBranch($user, $student->branch_id);
    }

    /**
     * Determine if user can create students
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('student.create');
    }

    /**
     * Determine if user can update student
     */
    public function update(User $user, Student $student): bool
    {
        if (!$user->hasPermission('student.update')) {
            return false;
        }

        return $this->canAccessBranch($user, $student->branch_id);
    }

    /**
     * Determine if user can delete student
     */
    public function delete(User $user, Student $student): bool
    {
        if (!$user->hasPermission('student.delete')) {
            return false;
        }

        return $this->canAccessBranch($user, $student->branch_id);
    }

    /**
     * Determine if user can restore student
     */
    public function restore(User $user, Student $student): bool
    {
        if (!$user->hasPermission('student.restore')) {
            return false;
        }

        return $this->canAccessBranch($user, $student->branch_id);
    }

    /**
     * Determine if user can force delete student
     */
    public function forceDelete(User $user, Student $student): bool
    {
        if (!$user->hasPermission('student.force_delete')) {
            return false;
        }

        return $this->canAccessBranch($user, $student->branch_id);
    }

    /**
     * Determine if user can view student's financial information
     */
    public function viewFinancial(User $user, Student $student): bool
    {
        if (!$user->hasPermission('student.view_financial')) {
            return false;
        }

        return $this->canAccessBranch($user, $student->branch_id);
    }

    /**
     * Determine if user can enroll student
     */
    public function enroll(User $user, Student $student): bool
    {
        if (!$user->hasPermission('student.enroll')) {
            return false;
        }

        return $this->canAccessBranch($user, $student->branch_id);
    }

    /**
     * Determine if user can record attendance for student
     */
    public function recordAttendance(User $user, Student $student): bool
    {
        if (!$user->hasPermission('attendance.record')) {
            return false;
        }

        return $this->canAccessBranch($user, $student->branch_id);
    }

    /**
     * Check if user can access branch
     */
    private function canAccessBranch(User $user, string $branchId): bool
    {
        // Organization-level users can access all branches
        if ($user->hasPermission('organization.manage')) {
            return true;
        }

        // Check if user has access to this specific branch
        return $user->branches->contains('id', $branchId);
    }
}

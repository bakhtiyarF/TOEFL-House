<?php

namespace App\Policies;

use App\Modules\Iam\Models\User;
use App\Modules\Academic\Models\AcademicClass;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class Policy
 *
 * Fine-grained authorization for class operations.
 */
class ClassPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any classes
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('class.view');
    }

    /**
     * Determine if user can view specific class
     */
    public function view(User $user, AcademicClass $class): bool
    {
        if (!$user->hasPermission('class.view')) {
            return false;
        }

        return $this->canAccessBranch($user, $class->branch_id);
    }

    /**
     * Determine if user can create classes
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('class.create');
    }

    /**
     * Determine if user can update class
     */
    public function update(User $user, AcademicClass $class): bool
    {
        if (!$user->hasPermission('class.update')) {
            return false;
        }

        return $this->canAccessBranch($user, $class->branch_id);
    }

    /**
     * Determine if user can delete class
     */
    public function delete(User $user, AcademicClass $class): bool
    {
        if (!$user->hasPermission('class.delete')) {
            return false;
        }

        return $this->canAccessBranch($user, $class->branch_id);
    }

    /**
     * Determine if user can manage sessions for class
     */
    public function manageSessions(User $user, AcademicClass $class): bool
    {
        if (!$user->hasPermission('session.manage')) {
            return false;
        }

        return $this->canAccessBranch($user, $class->branch_id);
    }

    /**
     * Determine if user can record attendance for class
     */
    public function recordAttendance(User $user, AcademicClass $class): bool
    {
        if (!$user->hasPermission('attendance.record')) {
            return false;
        }

        // Teachers can record attendance for their own classes
        if ($class->teacher_id === $user->teacher?->id) {
            return true;
        }

        return $this->canAccessBranch($user, $class->branch_id);
    }

    /**
     * Determine if user can enroll students in class
     */
    public function enrollStudents(User $user, AcademicClass $class): bool
    {
        if (!$user->hasPermission('class.enroll')) {
            return false;
        }

        return $this->canAccessBranch($user, $class->branch_id);
    }

    /**
     * Determine if user can view class roster
     */
    public function viewRoster(User $user, AcademicClass $class): bool
    {
        if (!$user->hasPermission('class.view_roster')) {
            return false;
        }

        // Teachers can view roster for their own classes
        if ($class->teacher_id === $user->teacher?->id) {
            return true;
        }

        return $this->canAccessBranch($user, $class->branch_id);
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

<?php

namespace App\Policies;

use App\Modules\Iam\Models\User;
use App\Modules\Academic\Models\Roster;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Roster Policy
 *
 * Fine-grained authorization for roster/attendance operations.
 */
class RosterPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any rosters
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('roster.view');
    }

    /**
     * Determine if user can view specific roster
     */
    public function view(User $user, Roster $roster): bool
    {
        if (!$user->hasPermission('roster.view')) {
            return false;
        }

        return $this->canAccessBranch($user, $roster->session->class->branch_id);
    }

    /**
     * Determine if user can create rosters
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('roster.create');
    }

    /**
     * Determine if user can update roster
     */
    public function update(User $user, Roster $roster): bool
    {
        if (!$user->hasPermission('roster.update')) {
            return false;
        }

        return $this->canAccessBranch($user, $roster->session->class->branch_id);
    }

    /**
     * Determine if user can delete roster
     */
    public function delete(User $user, Roster $roster): bool
    {
        if (!$user->hasPermission('roster.delete')) {
            return false;
        }

        return $this->canAccessBranch($user, $roster->session->class->branch_id);
    }

    /**
     * Determine if user can mark attendance
     */
    public function markAttendance(User $user, Roster $roster): bool
    {
        if (!$user->hasPermission('attendance.record')) {
            return false;
        }

        $class = $roster->session->class;

        // Teachers can mark attendance for their own classes
        if ($class->teacher_id === $user->teacher?->id) {
            return true;
        }

        return $this->canAccessBranch($user, $class->branch_id);
    }

    /**
     * Determine if user can view attendance reports
     */
    public function viewReports(User $user): bool
    {
        return $user->hasPermission('attendance.view_reports');
    }

    /**
     * Determine if user can export attendance
     */
    public function export(User $user): bool
    {
        return $user->hasPermission('attendance.export');
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

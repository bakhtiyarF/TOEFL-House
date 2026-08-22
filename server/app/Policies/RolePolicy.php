<?php

namespace App\Policies;

use App\Modules\Iam\Models\User;
use App\Modules\Iam\Models\Role;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Role Policy
 *
 * Fine-grained authorization for role operations.
 */
class RolePolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any roles
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('role.view');
    }

    /**
     * Determine if user can view specific role
     */
    public function view(User $user, Role $role): bool
    {
        if (!$user->hasPermission('role.view')) {
            return false;
        }

        return true; // Roles are organization-wide
    }

    /**
     * Determine if user can create roles
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('role.create');
    }

    /**
     * Determine if user can update role
     */
    public function update(User $user, Role $role): bool
    {
        if (!$user->hasPermission('role.update')) {
            return false;
        }

        // Cannot update system roles
        if ($role->is_system) {
            return false;
        }

        return true;
    }

    /**
     * Determine if user can delete role
     */
    public function delete(User $user, Role $role): bool
    {
        if (!$user->hasPermission('role.delete')) {
            return false;
        }

        // Cannot delete system roles
        if ($role->is_system) {
            return false;
        }

        // Cannot delete roles with active users
        if ($role->users_count > 0) {
            return false;
        }

        return true;
    }

    /**
     * Determine if user can assign permissions to role
     */
    public function assignPermissions(User $user, Role $role): bool
    {
        if (!$user->hasPermission('role.assign_permissions')) {
            return false;
        }

        return true;
    }

    /**
     * Determine if user can view role reports
     */
    public function viewReports(User $user): bool
    {
        return $user->hasPermission('role.view_reports');
    }
}

<?php

namespace App\Policies;

use App\Modules\Iam\Models\User;
use App\Modules\Iam\Models\Permission;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Permission Policy
 *
 * Fine-grained authorization for permission operations.
 */
class PermissionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any permissions
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('permission.view');
    }

    /**
     * Determine if user can view specific permission
     */
    public function view(User $user, Permission $permission): bool
    {
        if (!$user->hasPermission('permission.view')) {
            return false;
        }

        return true; // Permissions are organization-wide
    }

    /**
     * Determine if user can create permissions
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('permission.create');
    }

    /**
     * Determine if user can update permission
     */
    public function update(User $user, Permission $permission): bool
    {
        if (!$user->hasPermission('permission.update')) {
            return false;
        }

        // Cannot update system permissions
        if ($permission->is_system) {
            return false;
        }

        return true;
    }

    /**
     * Determine if user can delete permission
     */
    public function delete(User $user, Permission $permission): bool
    {
        if (!$user->hasPermission('permission.delete')) {
            return false;
        }

        // Cannot delete system permissions
        if ($permission->is_system) {
            return false;
        }

        // Cannot delete permissions assigned to roles
        if ($permission->roles_count > 0) {
            return false;
        }

        return true;
    }

    /**
     * Determine if user can view permission reports
     */
    public function viewReports(User $user): bool
    {
        return $user->hasPermission('permission.view_reports');
    }
}

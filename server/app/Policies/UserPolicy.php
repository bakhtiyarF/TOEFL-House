<?php

namespace App\Policies;

use App\Modules\Iam\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * User Policy
 *
 * Fine-grained authorization for user operations.
 */
class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any users
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('user.view');
    }

    /**
     * Determine if user can view specific user
     */
    public function view(User $user, User $model): bool
    {
        if (!$user->hasPermission('user.view')) {
            return false;
        }

        // Users can view their own profile
        if ($user->id === $model->id) {
            return true;
        }

        return $this->canAccessBranch($user, $model->branch_id);
    }

    /**
     * Determine if user can create users
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('user.create');
    }

    /**
     * Determine if user can update user
     */
    public function update(User $user, User $model): bool
    {
        if (!$user->hasPermission('user.update')) {
            return false;
        }

        // Users can update their own profile
        if ($user->id === $model->id) {
            return true;
        }

        return $this->canAccessBranch($user, $model->branch_id);
    }

    /**
     * Determine if user can delete user
     */
    public function delete(User $user, User $model): bool
    {
        if (!$user->hasPermission('user.delete')) {
            return false;
        }

        // Users cannot delete themselves
        if ($user->id === $model->id) {
            return false;
        }

        // Cannot delete organization admins
        if ($model->hasPermission('organization.manage')) {
            return false;
        }

        return $this->canAccessBranch($user, $model->branch_id);
    }

    /**
     * Determine if user can assign roles
     */
    public function assignRoles(User $user, User $model): bool
    {
        if (!$user->hasPermission('user.assign_roles')) {
            return false;
        }

        return $this->canAccessBranch($user, $model->branch_id);
    }

    /**
     * Determine if user can manage permissions
     */
    public function managePermissions(User $user, User $model): bool
    {
        if (!$user->hasPermission('user.manage_permissions')) {
            return false;
        }

        return $this->canAccessBranch($user, $model->branch_id);
    }

    /**
     * Determine if user can reset password
     */
    public function resetPassword(User $user, User $model): bool
    {
        if (!$user->hasPermission('user.reset_password')) {
            return false;
        }

        // Users can reset their own password
        if ($user->id === $model->id) {
            return true;
        }

        return $this->canAccessBranch($user, $model->branch_id);
    }

    /**
     * Determine if user can activate/deactivate user
     */
    public function toggleStatus(User $user, User $model): bool
    {
        if (!$user->hasPermission('user.toggle_status')) {
            return false;
        }

        // Users cannot deactivate themselves
        if ($user->id === $model->id) {
            return false;
        }

        return $this->canAccessBranch($user, $model->branch_id);
    }

    /**
     * Determine if user can view user reports
     */
    public function viewReports(User $user): bool
    {
        return $user->hasPermission('user.view_reports');
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

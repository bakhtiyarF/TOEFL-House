<?php

namespace App\Policies;

use App\Modules\Iam\Models\User;
use App\Modules\Iam\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Organization Policy
 *
 * Fine-grained authorization for organization operations.
 */
class OrganizationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any organizations
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('organization.view');
    }

    /**
     * Determine if user can view specific organization
     */
    public function view(User $user, Organization $organization): bool
    {
        if (!$user->hasPermission('organization.view')) {
            return false;
        }

        return true; // Organization is top-level
    }

    /**
     * Determine if user can create organizations
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('organization.create');
    }

    /**
     * Determine if user can update organization
     */
    public function update(User $user, Organization $organization): bool
    {
        if (!$user->hasPermission('organization.update')) {
            return false;
        }

        return true;
    }

    /**
     * Determine if user can delete organization
     */
    public function delete(User $user, Organization $organization): bool
    {
        if (!$user->hasPermission('organization.delete')) {
            return false;
        }

        // Cannot delete organization with active branches
        if ($organization->branches_count > 0) {
            return false;
        }

        return true;
    }

    /**
     * Determine if user can manage organization settings
     */
    public function manageSettings(User $user, Organization $organization): bool
    {
        if (!$user->hasPermission('organization.manage_settings')) {
            return false;
        }

        return true;
    }

    /**
     * Determine if user can view organization reports
     */
    public function viewReports(User $user, Organization $organization): bool
    {
        if (!$user->hasPermission('organization.view_reports')) {
            return false;
        }

        return true;
    }

    /**
     * Determine if user can manage organization billing
     */
    public function manageBilling(User $user, Organization $organization): bool
    {
        if (!$user->hasPermission('organization.manage_billing')) {
            return false;
        }

        return true;
    }
}

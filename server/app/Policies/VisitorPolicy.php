<?php

namespace App\Policies;

use App\Modules\Iam\Models\User;
use App\Modules\CrmEnrollment\Models\Visitor;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Visitor Policy
 *
 * Fine-grained authorization for visitor/lead operations.
 */
class VisitorPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any visitors
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('visitor.view');
    }

    /**
     * Determine if user can view specific visitor
     */
    public function view(User $user, Visitor $visitor): bool
    {
        if (!$user->hasPermission('visitor.view')) {
            return false;
        }

        return $this->canAccessBranch($user, $visitor->branch_id);
    }

    /**
     * Determine if user can create visitors
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('visitor.create');
    }

    /**
     * Determine if user can update visitor
     */
    public function update(User $user, Visitor $visitor): bool
    {
        if (!$user->hasPermission('visitor.update')) {
            return false;
        }

        return $this->canAccessBranch($user, $visitor->branch_id);
    }

    /**
     * Determine if user can delete visitor
     */
    public function delete(User $user, Visitor $visitor): bool
    {
        if (!$user->hasPermission('visitor.delete')) {
            return false;
        }

        // Prevent deletion if visitor is converted
        if ($visitor->isConverted()) {
            return false;
        }

        return $this->canAccessBranch($user, $visitor->branch_id);
    }

    /**
     * Determine if user can convert visitor to student
     */
    public function convert(User $user, Visitor $visitor): bool
    {
        if (!$user->hasPermission('visitor.convert')) {
            return false;
        }

        // Check if visitor is ready for conversion
        if (!$visitor->isConversionReady()) {
            return false;
        }

        return $this->canAccessBranch($user, $visitor->branch_id);
    }

    /**
     * Determine if user can assign visitor to campaign
     */
    public function assignCampaign(User $user, Visitor $visitor): bool
    {
        if (!$user->hasPermission('visitor.assign_campaign')) {
            return false;
        }

        return $this->canAccessBranch($user, $visitor->branch_id);
    }

    /**
     * Determine if user can add follow-up notes
     */
    public function addFollowUp(User $user, Visitor $visitor): bool
    {
        if (!$user->hasPermission('visitor.add_followup')) {
            return false;
        }

        return $this->canAccessBranch($user, $visitor->branch_id);
    }

    /**
     * Determine if user can view visitor's placement test results
     */
    public function viewPlacement(User $user, Visitor $visitor): bool
    {
        if (!$user->hasPermission('visitor.view_placement')) {
            return false;
        }

        return $this->canAccessBranch($user, $visitor->branch_id);
    }

    /**
     * Determine if user can record placement test for visitor
     */
    public function recordPlacement(User $user, Visitor $visitor): bool
    {
        if (!$user->hasPermission('visitor.record_placement')) {
            return false;
        }

        return $this->canAccessBranch($user, $visitor->branch_id);
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

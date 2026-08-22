<?php

namespace App\Policies;

use App\Modules\Iam\Models\User;
use App\Modules\FundingImpact\Models\Campaign;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Campaign Policy
 *
 * Fine-grained authorization for campaign operations.
 */
class CampaignPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any campaigns
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('campaign.view');
    }

    /**
     * Determine if user can view specific campaign
     */
    public function view(User $user, Campaign $campaign): bool
    {
        if (!$user->hasPermission('campaign.view')) {
            return false;
        }

        return $this->canAccessBranch($user, $campaign->branch_id);
    }

    /**
     * Determine if user can create campaigns
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('campaign.create');
    }

    /**
     * Determine if user can update campaign
     */
    public function update(User $user, Campaign $campaign): bool
    {
        if (!$user->hasPermission('campaign.update')) {
            return false;
        }

        return $this->canAccessBranch($user, $campaign->branch_id);
    }

    /**
     * Determine if user can delete campaign
     */
    public function delete(User $user, Campaign $campaign): bool
    {
        if (!$user->hasPermission('campaign.delete')) {
            return false;
        }

        // Prevent deletion if campaign has active visitors or donations
        if ($campaign->visitors()->count() > 0 || $campaign->donations()->count() > 0) {
            return false;
        }

        return $this->canAccessBranch($user, $campaign->branch_id);
    }

    /**
     * Determine if user can activate campaign
     */
    public function activate(User $user, Campaign $campaign): bool
    {
        if (!$user->hasPermission('campaign.activate')) {
            return false;
        }

        return $this->canAccessBranch($user, $campaign->branch_id);
    }

    /**
     * Determine if user can complete campaign
     */
    public function complete(User $user, Campaign $campaign): bool
    {
        if (!$user->hasPermission('campaign.complete')) {
            return false;
        }

        return $this->canAccessBranch($user, $campaign->branch_id);
    }

    /**
     * Determine if user can view campaign metrics
     */
    public function viewMetrics(User $user, Campaign $campaign): bool
    {
        if (!$user->hasPermission('campaign.view_metrics')) {
            return false;
        }

        return $this->canAccessBranch($user, $campaign->branch_id);
    }

    /**
     * Determine if user can add expense to campaign
     */
    public function addExpense(User $user, Campaign $campaign): bool
    {
        if (!$user->hasPermission('campaign.add_expense')) {
            return false;
        }

        return $this->canAccessBranch($user, $campaign->branch_id);
    }

    /**
     * Determine if user can view campaign reports
     */
    public function viewReports(User $user): bool
    {
        return $user->hasPermission('campaign.view_reports');
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

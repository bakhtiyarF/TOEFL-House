<?php

namespace App\Policies;

use App\Modules\Iam\Models\User;
use App\Modules\FundingImpact\Models\Donation;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Donation Policy
 *
 * Fine-grained authorization for donation operations.
 */
class DonationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any donations
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('donation.view');
    }

    /**
     * Determine if user can view specific donation
     */
    public function view(User $user, Donation $donation): bool
    {
        if (!$user->hasPermission('donation.view')) {
            return false;
        }

        return $this->canAccessBranch($user, $donation->branch_id);
    }

    /**
     * Determine if user can create donations
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('donation.create');
    }

    /**
     * Determine if user can update donation
     */
    public function update(User $user, Donation $donation): bool
    {
        if (!$user->hasPermission('donation.update')) {
            return false;
        }

        return $this->canAccessBranch($user, $donation->branch_id);
    }

    /**
     * Determine if user can delete donation
     */
    public function delete(User $user, Donation $donation): bool
    {
        if (!$user->hasPermission('donation.delete')) {
            return false;
        }

        // Prevent deletion if donation has been used for scholarships
        if ($donation->scholarships()->count() > 0) {
            return false;
        }

        return $this->canAccessBranch($user, $donation->branch_id);
    }

    /**
     * Determine if user can issue receipt for donation
     */
    public function issueReceipt(User $user, Donation $donation): bool
    {
        if (!$user->hasPermission('donation.issue_receipt')) {
            return false;
        }

        return $this->canAccessBranch($user, $donation->branch_id);
    }

    /**
     * Determine if user can view donation reports
     */
    public function viewReports(User $user): bool
    {
        return $user->hasPermission('donation.view_reports');
    }

    /**
     * Determine if user can export donations
     */
    public function export(User $user): bool
    {
        return $user->hasPermission('donation.export');
    }

    /**
     * Determine if user can refund donation
     */
    public function refund(User $user, Donation $donation): bool
    {
        if (!$user->hasPermission('donation.refund')) {
            return false;
        }

        // Only completed donations can be refunded
        if ($donation->status !== 'completed') {
            return false;
        }

        // Only donations within 90 days can be refunded
        if ($donation->donation_date->lt(now()->subDays(90))) {
            return false;
        }

        return $this->canAccessBranch($user, $donation->branch_id);
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

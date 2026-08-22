<?php

namespace App\Policies;

use App\Modules\Iam\Models\User;
use App\Modules\FinancePayroll\Models\Payment;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Payment Policy
 *
 * Fine-grained authorization for payment operations.
 */
class PaymentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any payments
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payment.view');
    }

    /**
     * Determine if user can view specific payment
     */
    public function view(User $user, Payment $payment): bool
    {
        if (!$user->hasPermission('payment.view')) {
            return false;
        }

        return $this->canAccessBranch($user, $payment->branch_id);
    }

    /**
     * Determine if user can create payments
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('payment.create');
    }

    /**
     * Determine if user can update payment
     */
    public function update(User $user, Payment $payment): bool
    {
        if (!$user->hasPermission('payment.update')) {
            return false;
        }

        return $this->canAccessBranch($user, $payment->branch_id);
    }

    /**
     * Determine if user can delete payment
     */
    public function delete(User $user, Payment $payment): bool
    {
        if (!$user->hasPermission('payment.delete')) {
            return false;
        }

        return $this->canAccessBranch($user, $payment->branch_id);
    }

    /**
     * Determine if user can refund payment
     */
    public function refund(User $user, Payment $payment): bool
    {
        if (!$user->hasPermission('payment.refund')) {
            return false;
        }

        // Only completed payments can be refunded
        if ($payment->status !== 'completed') {
            return false;
        }

        // Only payments within 30 days can be refunded
        if ($payment->date->lt(now()->subDays(30))) {
            return false;
        }

        return $this->canAccessBranch($user, $payment->branch_id);
    }

    /**
     * Determine if user can approve payment
     */
    public function approve(User $user, Payment $payment): bool
    {
        if (!$user->hasPermission('payment.approve')) {
            return false;
        }

        return $this->canAccessBranch($user, $payment->branch_id);
    }

    /**
     * Determine if user can view payment reports
     */
    public function viewReports(User $user): bool
    {
        return $user->hasPermission('payment.view_reports');
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

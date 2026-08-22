<?php

namespace App\Policies;

use App\Modules\Iam\Models\User;
use App\Modules\Academic\Models\Certificate;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Certificate Policy
 *
 * Fine-grained authorization for certificate operations.
 */
class CertificatePolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any certificates
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('certificate.view');
    }

    /**
     * Determine if user can view specific certificate
     */
    public function view(User $user, Certificate $certificate): bool
    {
        if (!$user->hasPermission('certificate.view')) {
            return false;
        }

        return $this->canAccessBranch($user, $certificate->branch_id);
    }

    /**
     * Determine if user can create certificates
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('certificate.create');
    }

    /**
     * Determine if user can update certificate
     */
    public function update(User $user, Certificate $certificate): bool
    {
        if (!$user->hasPermission('certificate.update')) {
            return false;
        }

        return $this->canAccessBranch($user, $certificate->branch_id);
    }

    /**
     * Determine if user can delete certificate
     */
    public function delete(User $user, Certificate $certificate): bool
    {
        if (!$user->hasPermission('certificate.delete')) {
            return false;
        }

        return $this->canAccessBranch($user, $certificate->branch_id);
    }

    /**
     * Determine if user can issue certificate
     */
    public function issue(User $user, Certificate $certificate): bool
    {
        if (!$user->hasPermission('certificate.issue')) {
            return false;
        }

        return $this->canAccessBranch($user, $certificate->branch_id);
    }

    /**
     * Determine if user can revoke certificate
     */
    public function revoke(User $user, Certificate $certificate): bool
    {
        if (!$user->hasPermission('certificate.revoke')) {
            return false;
        }

        return $this->canAccessBranch($user, $certificate->branch_id);
    }

    /**
     * Determine if user can verify certificate
     */
    public function verify(User $user): bool
    {
        return $user->hasPermission('certificate.verify');
    }

    /**
     * Determine if user can view certificate reports
     */
    public function viewReports(User $user): bool
    {
        return $user->hasPermission('certificate.view_reports');
    }

    /**
     * Determine if user can export certificates
     */
    public function export(User $user): bool
    {
        return $user->hasPermission('certificate.export');
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

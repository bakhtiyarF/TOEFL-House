<?php

namespace App\Policies;

use App\Modules\Iam\Models\User;
use App\Modules\FinancePayroll\Models\Invoice;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Invoice Policy
 *
 * Fine-grained authorization for invoice operations.
 */
class InvoicePolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any invoices
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('invoice.view');
    }

    /**
     * Determine if user can view specific invoice
     */
    public function view(User $user, Invoice $invoice): bool
    {
        if (!$user->hasPermission('invoice.view')) {
            return false;
        }

        return $this->canAccessBranch($user, $invoice->branch_id);
    }

    /**
     * Determine if user can create invoices
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('invoice.create');
    }

    /**
     * Determine if user can update invoice
     */
    public function update(User $user, Invoice $invoice): bool
    {
        if (!$user->hasPermission('invoice.update')) {
            return false;
        }

        // Only draft invoices can be updated
        if ($invoice->status !== 'draft') {
            return false;
        }

        return $this->canAccessBranch($user, $invoice->branch_id);
    }

    /**
     * Determine if user can delete invoice
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        if (!$user->hasPermission('invoice.delete')) {
            return false;
        }

        // Only draft invoices can be deleted
        if ($invoice->status !== 'draft') {
            return false;
        }

        return $this->canAccessBranch($user, $invoice->branch_id);
    }

    /**
     * Determine if user can issue invoice
     */
    public function issue(User $user, Invoice $invoice): bool
    {
        if (!$user->hasPermission('invoice.issue')) {
            return false;
        }

        // Only draft invoices can be issued
        if ($invoice->status !== 'draft') {
            return false;
        }

        return $this->canAccessBranch($user, $invoice->branch_id);
    }

    /**
     * Determine if user can mark invoice as paid
     */
    public function markAsPaid(User $user, Invoice $invoice): bool
    {
        if (!$user->hasPermission('invoice.mark_paid')) {
            return false;
        }

        // Only issued invoices can be marked as paid
        if ($invoice->status !== 'issued') {
            return false;
        }

        return $this->canAccessBranch($user, $invoice->branch_id);
    }

    /**
     * Determine if user can cancel invoice
     */
    public function cancel(User $user, Invoice $invoice): bool
    {
        if (!$user->hasPermission('invoice.cancel')) {
            return false;
        }

        // Only draft or issued invoices can be cancelled
        if (!in_array($invoice->status, ['draft', 'issued'])) {
            return false;
        }

        return $this->canAccessBranch($user, $invoice->branch_id);
    }

    /**
     * Determine if user can view invoice reports
     */
    public function viewReports(User $user): bool
    {
        return $user->hasPermission('invoice.view_reports');
    }

    /**
     * Determine if user can export invoices
     */
    public function export(User $user): bool
    {
        return $user->hasPermission('invoice.export');
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

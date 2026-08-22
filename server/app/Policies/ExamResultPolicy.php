<?php

namespace App\Policies;

use App\Modules\Iam\Models\User;
use App\Modules\Academic\Models\ExamResult;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * ExamResult Policy
 *
 * Fine-grained authorization for exam result operations.
 */
class ExamResultPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any exam results
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('exam_result.view');
    }

    /**
     * Determine if user can view specific exam result
     */
    public function view(User $user, ExamResult $examResult): bool
    {
        if (!$user->hasPermission('exam_result.view')) {
            return false;
        }

        // Students can view their own results
        if ($user->student && $user->student->id === $examResult->student_id) {
            return true;
        }

        return $this->canAccessBranch($user, $examResult->branch_id);
    }

    /**
     * Determine if user can create exam results
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('exam_result.create');
    }

    /**
     * Determine if user can update exam result
     */
    public function update(User $user, ExamResult $examResult): bool
    {
        if (!$user->hasPermission('exam_result.update')) {
            return false;
        }

        // Only unpublished results can be updated
        if ($examResult->is_published) {
            return false;
        }

        return $this->canAccessBranch($user, $examResult->branch_id);
    }

    /**
     * Determine if user can delete exam result
     */
    public function delete(User $user, ExamResult $examResult): bool
    {
        if (!$user->hasPermission('exam_result.delete')) {
            return false;
        }

        // Only unpublished results can be deleted
        if ($examResult->is_published) {
            return false;
        }

        return $this->canAccessBranch($user, $examResult->branch_id);
    }

    /**
     * Determine if user can publish exam result
     */
    public function publish(User $user, ExamResult $examResult): bool
    {
        if (!$user->hasPermission('exam_result.publish')) {
            return false;
        }

        return $this->canAccessBranch($user, $examResult->branch_id);
    }

    /**
     * Determine if user can view exam result reports
     */
    public function viewReports(User $user): bool
    {
        return $user->hasPermission('exam_result.view_reports');
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

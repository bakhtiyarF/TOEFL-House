<?php

namespace App\Policies;

use App\Modules\Iam\Models\User;
use App\Modules\Academic\Models\Exam;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Exam Policy
 *
 * Fine-grained authorization for exam operations.
 */
class ExamPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any exams
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('exam.view');
    }

    /**
     * Determine if user can view specific exam
     */
    public function view(User $user, Exam $exam): bool
    {
        if (!$user->hasPermission('exam.view')) {
            return false;
        }

        return $this->canAccessBranch($user, $exam->branch_id);
    }

    /**
     * Determine if user can create exams
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('exam.create');
    }

    /**
     * Determine if user can update exam
     */
    public function update(User $user, Exam $exam): bool
    {
        if (!$user->hasPermission('exam.update')) {
            return false;
        }

        // Only scheduled exams can be updated
        if ($exam->status === 'completed') {
            return false;
        }

        return $this->canAccessBranch($user, $exam->branch_id);
    }

    /**
     * Determine if user can delete exam
     */
    public function delete(User $user, Exam $exam): bool
    {
        if (!$user->hasPermission('exam.delete')) {
            return false;
        }

        // Only scheduled exams can be deleted
        if ($exam->status !== 'scheduled') {
            return false;
        }

        return $this->canAccessBranch($user, $exam->branch_id);
    }

    /**
     * Determine if user can schedule exam
     */
    public function schedule(User $user, Exam $exam): bool
    {
        if (!$user->hasPermission('exam.schedule')) {
            return false;
        }

        return $this->canAccessBranch($user, $exam->branch_id);
    }

    /**
     * Determine if user can conduct exam
     */
    public function conduct(User $user, Exam $exam): bool
    {
        if (!$user->hasPermission('exam.conduct')) {
            return false;
        }

        return $this->canAccessBranch($user, $exam->branch_id);
    }

    /**
     * Determine if user can grade exam
     */
    public function grade(User $user, Exam $exam): bool
    {
        if (!$user->hasPermission('exam.grade')) {
            return false;
        }

        return $this->canAccessBranch($user, $exam->branch_id);
    }

    /**
     * Determine if user can publish exam results
     */
    public function publishResults(User $user, Exam $exam): bool
    {
        if (!$user->hasPermission('exam.publish_results')) {
            return false;
        }

        return $this->canAccessBranch($user, $exam->branch_id);
    }

    /**
     * Determine if user can view exam reports
     */
    public function viewReports(User $user): bool
    {
        return $user->hasPermission('exam.view_reports');
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

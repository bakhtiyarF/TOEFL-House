<?php

namespace App\Policies;

use App\Modules\Iam\Models\User;
use App\Modules\Academic\Models\Homework;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Homework Policy
 *
 * Fine-grained authorization for homework operations.
 */
class HomeworkPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any homework
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('homework.view');
    }

    /**
     * Determine if user can view specific homework
     */
    public function view(User $user, Homework $homework): bool
    {
        if (!$user->hasPermission('homework.view')) {
            return false;
        }

        return $this->canAccessBranch($user, $homework->session->class->branch_id);
    }

    /**
     * Determine if user can create homework
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('homework.create');
    }

    /**
     * Determine if user can update homework
     */
    public function update(User $user, Homework $homework): bool
    {
        if (!$user->hasPermission('homework.update')) {
            return false;
        }

        $class = $homework->session->class;

        // Teachers can update homework for their own classes
        if ($class->teacher_id === $user->teacher?->id) {
            return true;
        }

        return $this->canAccessBranch($user, $class->branch_id);
    }

    /**
     * Determine if user can delete homework
     */
    public function delete(User $user, Homework $homework): bool
    {
        if (!$user->hasPermission('homework.delete')) {
            return false;
        }

        $class = $homework->session->class;

        // Teachers can delete homework for their own classes
        if ($class->teacher_id === $user->teacher?->id) {
            return true;
        }

        return $this->canAccessBranch($user, $class->branch_id);
    }

    /**
     * Determine if user can grade homework
     */
    public function grade(User $user, Homework $homework): bool
    {
        if (!$user->hasPermission('homework.grade')) {
            return false;
        }

        $class = $homework->session->class;

        // Teachers can grade homework for their own classes
        if ($class->teacher_id === $user->teacher?->id) {
            return true;
        }

        return $this->canAccessBranch($user, $class->branch_id);
    }

    /**
     * Determine if user can submit homework
     */
    public function submit(User $user, Homework $homework): bool
    {
        if (!$user->hasPermission('homework.submit')) {
            return false;
        }

        // Students can only submit their own homework
        return $user->student && $homework->session->class->students->contains($user->student->id);
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

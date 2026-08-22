<?php

namespace App\Modules\Academic\Policies;

use App\Modules\Academic\Models\Student;
use App\Modules\Iam\Models\User;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('Student.View');
    }

    public function view(User $user, Student $student): bool
    {
        return $user->hasPermission('Student.View') &&
               app(\App\Modules\Iam\Services\BranchScopeService::class)
                   ->canAccessBranch($user, $student->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('Student.Create');
    }

    public function update(User $user, Student $student): bool
    {
        return $user->hasPermission('Student.Update') &&
               app(\App\Modules\Iam\Services\BranchScopeService::class)
                   ->canAccessBranch($user, $student->branch_id);
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->hasPermission('Student.Delete') &&
               app(\App\Modules\Iam\Services\BranchScopeService::class)
                   ->canAccessBranch($user, $student->branch_id);
    }
}

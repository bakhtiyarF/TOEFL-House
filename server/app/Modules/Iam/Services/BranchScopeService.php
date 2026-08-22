<?php

namespace App\Modules\Iam\Services;

use App\Modules\Iam\Models\User;

/**
 * Branch Scope Service
 * Implements 02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md §5.5 exactly
 * Including the role === 'manager' hardcoded carve-out
 */
class BranchScopeService
{
    public function __construct(
        private PermissionResolutionService $permissionService
    ) {}

    /**
     * Resolve branch scope for a user requesting data
     *
     * @return array{branchId: string|null, isAll: bool}
     */
    public function resolve(User $user, ?string $requestedBranchId = null): array
    {
        $permissions = $this->permissionService->resolve($user);

        $hasOrgScope = ($user->role === 'owner') || $this->hasScopeLevel($permissions, 'organization');
        $hasCampusScope = $hasOrgScope || $this->hasScopeLevel($permissions, 'campus');

        if ($requestedBranchId === 'all') {
            if ($hasOrgScope || $user->role === 'manager') {
                return ['branchId' => null, 'isAll' => true];
            }
            // Request silently downgraded
            return ['branchId' => $user->branch_id, 'isAll' => false];
        }

        if ($requestedBranchId !== null && $requestedBranchId !== $user->branch_id) {
            if ($hasOrgScope || $hasCampusScope || $user->role === 'manager') {
                return ['branchId' => $requestedBranchId, 'isAll' => false];
            }
            // Cross-branch request silently downgraded
            return ['branchId' => $user->branch_id, 'isAll' => false];
        }

        return ['branchId' => $requestedBranchId ?? $user->branch_id, 'isAll' => false];
    }

    /**
     * Check if a user can access a specific branch
     */
    public function canAccessBranch(User $user, string $targetBranchId): bool
    {
        $result = $this->resolve($user, $targetBranchId);
        return $result['isAll'] || $result['branchId'] === $targetBranchId;
    }

    private function hasScopeLevel(array $permissions, string $targetScope): bool
    {
        $scopeRank = [
            'own' => 0, 'class' => 1, 'program' => 2,
            'department' => 3, 'branch' => 4, 'campus' => 5, 'organization' => 6,
        ];

        $targetRank = $scopeRank[$targetScope] ?? 4;

        foreach ($permissions as $perm) {
            $permRank = $scopeRank[$perm['scope']] ?? 4;
            if ($permRank >= $targetRank) {
                return true;
            }
        }

        return false;
    }
}

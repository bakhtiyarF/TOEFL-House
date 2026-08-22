<?php

namespace App\Modules\Iam\Services;

use App\Modules\Iam\Models\User;
use App\Modules\Iam\Models\UserRole;
use App\Modules\Iam\Models\RoleDelegation;
use App\Modules\Iam\Models\PermissionOverride;

/**
 * Permission Resolution Service
 * Implements 02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md §5.4 exactly
 */
class PermissionResolutionService
{
    /**
     * Scope hierarchy rank — narrower (lower) wins
     * 02 §5.3: organization(6) > campus(5) > branch(4) > department(3) > program(2) > class(1) > own(0)
     */
    private const SCOPE_RANK = [
        'own' => 0,
        'class' => 1,
        'program' => 2,
        'department' => 3,
        'branch' => 4,
        'campus' => 5,
        'organization' => 6,
    ];

    /**
     * Legacy role → permission map fallback (02 §5.1, §5.4 step 4)
     */
    private const LEGACY_ROLE_MAP = [
        'owner' => [
            'Dashboard.View' => 'organization', 'Dashboard.Executive' => 'organization',
            'Student.View' => 'organization', 'Student.Create' => 'organization',
            'Student.Edit' => 'organization', 'Class.View' => 'organization',
            'Class.Create' => 'organization', 'Class.Edit' => 'organization',
            'User.View' => 'organization', 'User.Create' => 'organization',
            'User.Edit' => 'organization', 'User.Delete' => 'organization',
        ],
        'manager' => [
            'Dashboard.View' => 'branch', 'Student.View' => 'branch',
            'Student.Create' => 'branch', 'Student.Edit' => 'branch',
            'Class.View' => 'branch', 'Payment.Create' => 'branch',
            'Budget.Allocate' => 'branch',
        ],
        'teacher' => [
            'Dashboard.View' => 'own', 'Student.View' => 'class',
            'Class.View' => 'own', 'Session.View' => 'own',
            'Session.Edit' => 'own', 'Attendance.View' => 'own',
            'Attendance.Edit' => 'own', 'Exam.View' => 'own',
            'Grade.View' => 'own', 'Grade.Edit' => 'own',
        ],
    ];

    /**
     * Resolve all effective permissions for a user
     */
    public function resolve(User $user): array
    {
        $permissions = [];

        // Step 1: Base grant from user_roles + role_permissions
        $this->resolveRolePermissions($user, $permissions);

        // Step 2: Role delegations (additive, never override)
        $this->resolveDelegations($user, $permissions);

        // Step 3: Permission overrides (grant adds, deny removes)
        $this->resolveOverrides($user, $permissions);

        // Step 4: Legacy fallback (only if no permissions resolved)
        if (empty($permissions)) {
            $this->resolveLegacyFallback($user, $permissions);
        }

        return $permissions;
    }

    private function resolveRolePermissions(User $user, array &$permissions): void
    {
        $userRoles = UserRole::where('user_id', $user->id)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->with('role.permissions')
            ->get();

        foreach ($userRoles as $userRole) {
            foreach ($userRole->role->permissions as $permission) {
                // Scope = narrower of role's default_scope and assignment's scope_type
                $effectiveScope = $this->narrowerScope(
                    $permission->pivot->default_scope ?? 'branch',
                    $userRole->scope_type ?? 'branch'
                );

                $permissions[$permission->code] = [
                    'code' => $permission->code,
                    'scope' => $effectiveScope,
                    'source' => 'role',
                ];
            }
        }
    }

    private function resolveDelegations(User $user, array &$permissions): void
    {
        $now = now();
        $delegations = RoleDelegation::where('to_user_id', $user->id)
            ->where('is_active', true)
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>', $now)
            ->with('role.permissions')
            ->get();

        foreach ($delegations as $delegation) {
            foreach ($delegation->role->permissions as $permission) {
                // Only add if not already granted
                if (!isset($permissions[$permission->code])) {
                    $permissions[$permission->code] = [
                        'code' => $permission->code,
                        'scope' => $delegation->scope_type ?? 'branch',
                        'source' => 'delegation',
                    ];
                }
            }
        }
    }

    private function resolveOverrides(User $user, array &$permissions): void
    {
        $overrides = PermissionOverride::where('user_id', $user->id)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->with('permission')
            ->get();

        foreach ($overrides as $override) {
            if ($override->effect === 'grant') {
                $permissions[$override->permission->code] = [
                    'code' => $override->permission->code,
                    'scope' => $override->scope_type ?? 'branch',
                    'source' => 'override',
                ];
            } elseif ($override->effect === 'deny') {
                unset($permissions[$override->permission->code]);
            }
        }
    }

    private function resolveLegacyFallback(User $user, array &$permissions): void
    {
        $legacyPerms = self::LEGACY_ROLE_MAP[$user->role] ?? [];

        foreach ($legacyPerms as $code => $scope) {
            $permissions[$code] = [
                'code' => $code,
                'scope' => $scope,
                'source' => 'role',
            ];
        }
    }

    /**
     * Return the narrower of two scopes (lower rank wins)
     */
    private function narrowerScope(string $a, string $b): string
    {
        $rankA = self::SCOPE_RANK[$a] ?? 4;
        $rankB = self::SCOPE_RANK[$b] ?? 4;
        return $rankA <= $rankB ? $a : $b;
    }
}

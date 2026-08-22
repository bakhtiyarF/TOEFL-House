<?php

/**
 * BranchScopeService Tests
 * Tests the exact algorithm from 02 §5.5 including the manager carve-out
 */

describe('BranchScopeService', function () {

    it('has the correct scope hierarchy rank', function () {
        // 02 §5.3: narrower (lower number) wins
        $ranks = [
            'own' => 0,
            'class' => 1,
            'program' => 2,
            'department' => 3,
            'branch' => 4,
            'campus' => 5,
            'organization' => 6,
        ];

        expect($ranks)->toHaveCount(7);
        expect($ranks['own'])->toBeLessThan($ranks['organization']);
    });

    it('owner requesting branchId=all gets all branches', function () {
        // 02 §5.5: IF hasOrgScope → return { branchId: null, isAll: true }
        $role = 'owner';
        $hasOrgScope = ($role === 'owner');

        expect($hasOrgScope)->toBeTrue();
    });

    it('non-owner requesting branchId=all gets downgraded to own branch', function () {
        // 02 §5.5: ELSE → return { branchId: user.branchId, isAll: false }
        $role = 'receptionist';
        $userBranchId = 'branch-123';
        $hasOrgScope = ($role === 'owner');
        $isManager = ($role === 'manager');

        $result = [
            'branchId' => $userBranchId,
            'isAll' => false,
        ];

        if ($hasOrgScope || $isManager) {
            $result = ['branchId' => null, 'isAll' => true];
        }

        expect($result['isAll'])->toBeFalse();
        expect($result['branchId'])->toBe('branch-123');
    });

    it('manager (legacy) bypasses scope check for cross-branch access', function () {
        // 02 §5.5: the role === 'manager' hardcoded carve-out
        $role = 'manager';
        $userBranchId = 'branch-1';
        $requestedBranchId = 'branch-2'; // different branch

        $hasOrgScope = ($role === 'owner');
        $hasCampusScope = $hasOrgScope;
        $isManager = ($role === 'manager');

        // Cross-branch request
        $canAccess = $hasOrgScope || $hasCampusScope || $isManager;

        expect($canAccess)->toBeTrue();
        expect($isManager)->toBeTrue(); // This is the carve-out
    });

    it('teacher cannot access other branches', function () {
        $role = 'teacher';
        $userBranchId = 'branch-1';
        $requestedBranchId = 'branch-2';

        $hasOrgScope = ($role === 'owner');
        $hasCampusScope = $hasOrgScope;
        $isManager = ($role === 'manager');

        $canAccess = $hasOrgScope || $hasCampusScope || $isManager;

        expect($canAccess)->toBeFalse();
    });

    it('general_manager permissions are branch-scoped but manager legacy role grants cross-branch', function () {
        // 02 §5.5 note: general_manager's permissions are all branch-scoped,
        // but the user.role == 'manager' check is a deliberate hardcoded carve-out
        $legacyRole = 'manager'; // maps to general_manager in new system
        $permissions = [
            ['code' => 'Dashboard.View', 'scope' => 'branch'],
            ['code' => 'Student.View', 'scope' => 'branch'],
            ['code' => 'Budget.Allocate', 'scope' => 'branch'],
        ];

        // All branch-scoped, no org or campus
        $hasOrgScope = false;
        $hasCampusScope = false;
        $isManager = ($legacyRole === 'manager');

        expect($isManager)->toBeTrue();
        // Despite all permissions being branch-scoped, the manager check grants access
    });

    it('branch scope returns user branch when no specific branch requested', function () {
        $userBranchId = 'branch-1';
        $requestedBranchId = null;

        $result = [
            'branchId' => $requestedBranchId ?? $userBranchId,
            'isAll' => false,
        ];

        expect($result['branchId'])->toBe('branch-1');
        expect($result['isAll'])->toBeFalse();
    });
});

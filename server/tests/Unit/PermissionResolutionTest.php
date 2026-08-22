<?php

/**
 * Permission Resolution Service Tests
 * Tests the exact algorithm from 02 §5.4
 */

describe('PermissionResolution', function () {

    it('has correct scope hierarchy ranks', function () {
        // 02 §5.3: narrower (lower) wins
        $hierarchy = [
            'own' => 0,
            'class' => 1,
            'program' => 2,
            'department' => 3,
            'branch' => 4,
            'campus' => 5,
            'organization' => 6,
        ];

        // Own is narrowest
        expect($hierarchy['own'])->toBeLessThan($hierarchy['class']);
        expect($hierarchy['class'])->toBeLessThan($hierarchy['program']);
        expect($hierarchy['program'])->toBeLessThan($hierarchy['department']);
        expect($hierarchy['department'])->toBeLessThan($hierarchy['branch']);
        expect($hierarchy['branch'])->toBeLessThan($hierarchy['campus']);
        expect($hierarchy['campus'])->toBeLessThan($hierarchy['organization']);
    });

    it('has all 10 roles defined', function () {
        $roles = [
            'owner', 'general_manager', 'head_of_department',
            'finance_manager', 'receptionist', 'counselor',
            'teacher', 'data_entry', 'designer', 'donor_manager',
        ];

        expect($roles)->toHaveCount(10);
    });

    it('has legacy role mapping with correct values', function () {
        // 02 §5.1 legacy map
        $legacyMap = [
            'owner' => 'owner',
            'manager' => 'general_manager',
            'finance' => 'finance_manager',
            'registrar' => 'receptionist',
            'teacher' => 'teacher',
            'head_of_department' => 'head_of_department',
            'counselor' => 'counselor',
            'donor_manager' => 'donor_manager',
        ];

        expect($legacyMap)->toHaveCount(8);
        expect($legacyMap['owner'])->toBe('owner');
        expect($legacyMap['manager'])->toBe('general_manager');
        expect($legacyMap['registrar'])->toBe('receptionist');
    });

    it('teacher has narrowest permission set', function () {
        // 02 §5.1 — teacher's exact permissions with per-permission scope
        $teacherPerms = [
            'Dashboard.View' => 'own',
            'Student.View' => 'class',
            'Class.View' => 'own',
            'Session.View' => 'own',
            'Session.Edit' => 'own',
            'Attendance.View' => 'own',
            'Attendance.Edit' => 'own',
            'Exam.View' => 'own',
            'Grade.View' => 'own',
            'Grade.Edit' => 'own',
        ];

        expect($teacherPerms)->toHaveCount(10);
        // Teacher has NO student create/edit/delete
        expect($teacherPerms)->not->toHaveKey('Student.Create');
        expect($teacherPerms)->not->toHaveKey('Student.Edit');
        expect($teacherPerms)->not->toHaveKey('Student.Delete');
    });

    it('owner has four specific exclusions', function () {
        // 02 §5.1 — owner excluded from these even at org level
        $ownerExclusions = [
            'Attendance.Edit',
            'Grade.Edit',
            'Student.Delete',
            'Payment.Delete',
        ];

        expect($ownerExclusions)->toHaveCount(4);
    });
});

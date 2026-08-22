<?php

namespace Database\Seeders;

use App\Modules\Iam\Models\Permission;
use App\Modules\Iam\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * IAM Seed Data
 * Based on 02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md §5.1–5.2
 * Per 04 §6: permissions, roles, role_permissions in order
 */
class IamSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = $this->seedPermissions();
        $roles = $this->seedRoles();
        $this->seedRolePermissions($roles, $permissions);
    }

    private function seedPermissions(): array
    {
        // Full permission catalog from 02 §5.2
        $catalog = [
            // Dashboard
            'Dashboard.View' => 'dashboard',
            'Dashboard.Executive' => 'dashboard',
            'Analytics.View' => 'dashboard',
            'Impact.View' => 'dashboard',

            // Academic
            'Student.View' => 'academic',
            'Student.Create' => 'academic',
            'Student.Edit' => 'academic',
            'Student.Delete' => 'academic',
            'Class.View' => 'academic',
            'Class.Create' => 'academic',
            'Class.Edit' => 'academic',
            'Class.Delete' => 'academic',
            'Session.View' => 'academic',
            'Session.Create' => 'academic',
            'Session.Edit' => 'academic',
            'Exam.View' => 'academic',
            'Exam.Create' => 'academic',
            'Exam.Edit' => 'academic',
            'Grade.View' => 'academic',
            'Grade.Edit' => 'academic',
            'Attendance.View' => 'academic',
            'Attendance.Edit' => 'academic',
            'Promotion.Approve' => 'academic',
            'Certificate.View' => 'academic',
            'Certificate.Create' => 'academic',

            // Admissions (CRM)
            'Lead.View' => 'admissions',
            'Lead.Create' => 'admissions',
            'Lead.Edit' => 'admissions',
            'Lead.Delete' => 'admissions',
            'Lead.Convert' => 'admissions',

            // HR
            'Teacher.View' => 'hr',
            'Teacher.Create' => 'hr',
            'Teacher.Edit' => 'hr',
            'Teacher.Delete' => 'hr',
            'Employee.View' => 'hr',
            'Employee.Create' => 'hr',
            'Employee.Edit' => 'hr',
            'Employee.Delete' => 'hr',
            'Payroll.View' => 'hr',
            'Payroll.Process' => 'hr',

            // Finance
            'Payment.View' => 'finance',
            'Payment.Create' => 'finance',
            'Payment.Edit' => 'finance',
            'Payment.Delete' => 'finance',
            'Invoice.View' => 'finance',
            'Invoice.Create' => 'finance',
            'Invoice.Edit' => 'finance',
            'Refund.Create' => 'finance',
            'Discount.Create' => 'finance',
            'Budget.View' => 'finance',
            'Budget.Allocate' => 'finance',
            'Expense.View' => 'finance',
            'Expense.Create' => 'finance',
            'Expense.Approve' => 'finance',
            'FeeStructure.Edit' => 'finance',
            'Finance.Report' => 'finance',
            'Ledger.View' => 'finance',

            // Inventory
            'Book.View' => 'inventory',
            'Book.Create' => 'inventory',
            'Book.Edit' => 'inventory',
            'Book.Delete' => 'inventory',
            'Book.Sell' => 'inventory',
            'Book.Refund' => 'inventory',

            // Funding
            'Funding.View' => 'funding',
            'Funding.Create' => 'funding',
            'Funding.Edit' => 'funding',
            'Funding.Delete' => 'funding',

            // Automation
            'Workflow.View' => 'automation',
            'Workflow.Create' => 'automation',
            'Workflow.Edit' => 'automation',
            'Rule.View' => 'automation',
            'Rule.Create' => 'automation',
            'Rule.Edit' => 'automation',

            // Reporting
            'Report.View' => 'reporting',

            // Security
            'User.View' => 'security',
            'User.Create' => 'security',
            'User.Edit' => 'security',
            'User.Delete' => 'security',
            'Role.View' => 'security',
            'Role.Create' => 'security',
            'Role.Edit' => 'security',
            'Permission.View' => 'security',
            'Audit.View' => 'security',
            'Event.View' => 'security',
            'Settings.View' => 'security',
            'Settings.Edit' => 'security',
            'Branch.View' => 'security',
            'Branch.Create' => 'security',
            'Branch.Edit' => 'security',
            'Branch.Delete' => 'security',
            'AcademicSetup.View' => 'security',
            'AcademicSetup.Edit' => 'security',
        ];

        $permissions = [];
        foreach ($catalog as $code => $category) {
            [$resource, $action] = explode('.', $code, 2);
            $permissions[$code] = Permission::create([
                'id' => Str::uuid()->toString(),
                'code' => $code,
                'resource' => $resource,
                'action' => $action,
                'category' => $category,
                'is_system' => true,
                'created_at' => now(),
            ]);
        }

        return $permissions;
    }

    private function seedRoles(): array
    {
        $roleDefinitions = [
            ['code' => 'owner', 'name' => 'Course Owner', 'sort_order' => 1, 'is_system' => true],
            ['code' => 'general_manager', 'name' => 'General Manager', 'sort_order' => 2, 'is_system' => true],
            ['code' => 'head_of_department', 'name' => 'Head of Department', 'sort_order' => 3, 'is_system' => true],
            ['code' => 'finance_manager', 'name' => 'Finance Manager', 'sort_order' => 4, 'is_system' => true],
            ['code' => 'receptionist', 'name' => 'Receptionist', 'sort_order' => 5, 'is_system' => true],
            ['code' => 'counselor', 'name' => 'Counselor', 'sort_order' => 6, 'is_system' => true],
            ['code' => 'teacher', 'name' => 'Teacher', 'sort_order' => 7, 'is_system' => true],
            ['code' => 'data_entry', 'name' => 'Data Entry', 'sort_order' => 8, 'is_system' => true],
            ['code' => 'designer', 'name' => 'Designer', 'sort_order' => 9, 'is_system' => true],
            ['code' => 'donor_manager', 'name' => 'Donor Manager', 'sort_order' => 10, 'is_system' => true],
        ];

        $roles = [];
        foreach ($roleDefinitions as $def) {
            $roles[$def['code']] = Role::create([
                'id' => Str::uuid()->toString(),
                ...$def,
            ]);
        }

        return $roles;
    }

    private function seedRolePermissions(array $roles, array $permissions): void
    {
        // Owner: organization scope on nearly everything, with 4 exclusions
        $ownerPerms = [
            'Dashboard.View' => 'organization', 'Dashboard.Executive' => 'organization',
            'Analytics.View' => 'organization', 'Impact.View' => 'organization',
            'Student.View' => 'organization', 'Student.Create' => 'organization', 'Student.Edit' => 'organization',
            'Class.View' => 'organization', 'Class.Create' => 'organization', 'Class.Edit' => 'organization',
            'Session.View' => 'organization', 'Session.Create' => 'organization', 'Session.Edit' => 'organization',
            'Exam.View' => 'organization', 'Exam.Create' => 'organization', 'Exam.Edit' => 'organization',
            'Grade.View' => 'organization',
            'Attendance.View' => 'organization',
            'Promotion.Approve' => 'organization',
            'Certificate.View' => 'organization', 'Certificate.Create' => 'organization',
            'Lead.View' => 'organization', 'Lead.Create' => 'organization', 'Lead.Edit' => 'organization', 'Lead.Convert' => 'organization',
            'Teacher.View' => 'organization', 'Teacher.Create' => 'organization', 'Teacher.Edit' => 'organization',
            'Employee.View' => 'organization', 'Employee.Create' => 'organization', 'Employee.Edit' => 'organization',
            'Payroll.View' => 'organization', 'Payroll.Process' => 'organization',
            'Payment.View' => 'organization', 'Payment.Create' => 'organization', 'Payment.Edit' => 'organization',
            'Invoice.View' => 'organization', 'Invoice.Create' => 'organization',
            'Budget.View' => 'organization', 'Budget.Allocate' => 'organization',
            'Expense.View' => 'organization', 'Expense.Create' => 'organization', 'Expense.Approve' => 'organization',
            'Finance.Report' => 'organization', 'Ledger.View' => 'organization',
            'Book.View' => 'organization', 'Book.Create' => 'organization', 'Book.Sell' => 'organization',
            'Funding.View' => 'organization', 'Funding.Create' => 'organization', 'Funding.Edit' => 'organization',
            'Workflow.View' => 'organization', 'Rule.View' => 'organization',
            'Report.View' => 'organization',
            'User.View' => 'organization', 'User.Create' => 'organization', 'User.Edit' => 'organization', 'User.Delete' => 'organization',
            'Role.View' => 'organization', 'Permission.View' => 'organization',
            'Audit.View' => 'organization', 'Settings.View' => 'organization', 'Settings.Edit' => 'organization',
            'Branch.View' => 'organization', 'Branch.Create' => 'organization', 'Branch.Edit' => 'organization',
        ];
        // Excluded for owner: Attendance.Edit, Grade.Edit, Student.Delete, Payment.Delete

        foreach ($ownerPerms as $code => $scope) {
            if (isset($permissions[$code])) {
                $roles['owner']->permissions()->attach($permissions[$code]->id, [
                    'id' => Str::uuid()->toString(),
                    'default_scope' => $scope,
                ]);
            }
        }

        // Teacher: narrowest role, per-permission mixed scopes
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

        foreach ($teacherPerms as $code => $scope) {
            if (isset($permissions[$code])) {
                $roles['teacher']->permissions()->attach($permissions[$code]->id, [
                    'id' => Str::uuid()->toString(),
                    'default_scope' => $scope,
                ]);
            }
        }

        // Receptionist: branch scope
        $receptionistPerms = [
            'Dashboard.View' => 'branch', 'Student.View' => 'branch', 'Student.Create' => 'branch', 'Student.Edit' => 'branch',
            'Class.View' => 'branch', 'Lead.View' => 'branch', 'Lead.Create' => 'branch', 'Lead.Edit' => 'branch', 'Lead.Convert' => 'branch',
            'Payment.View' => 'branch', 'Payment.Create' => 'branch',
            'Book.View' => 'branch', 'Book.Sell' => 'branch',
            'Attendance.View' => 'branch', 'Attendance.Edit' => 'branch',
        ];

        foreach ($receptionistPerms as $code => $scope) {
            if (isset($permissions[$code])) {
                $roles['receptionist']->permissions()->attach($permissions[$code]->id, [
                    'id' => Str::uuid()->toString(),
                    'default_scope' => $scope,
                ]);
            }
        }
    }
}

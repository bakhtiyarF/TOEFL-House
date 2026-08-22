<?php

use App\Modules\Academic\Models\AcademicClass;
use App\Modules\Academic\Models\Enrollment;
use App\Modules\Academic\Models\Student;
use App\Modules\FinancePayroll\Models\Invoice;
use App\Modules\FinancePayroll\Models\Payment;
use App\Modules\Iam\Models\Branch;
use App\Modules\Iam\Models\Permission;
use App\Modules\Iam\Models\Role;
use App\Modules\Iam\Models\User;
use App\Modules\PeopleHr\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Permission and Authorization', function () {
    
    it('can assign permissions to roles', function () {
        $role = Role::factory()->create(['name' => 'teacher']);
        $permissions = Permission::factory()->count(5)->create();

        $role->permissions()->attach($permissions->pluck('id'));

        expect($role->permissions()->count())->toBe(5);
        expect($role->hasPermission($permissions->first()->name))->toBeTrue();
    });

    it('can assign roles to users', function () {
        $user = User::factory()->create();
        $roles = Role::factory()->count(3)->create();

        $user->roles()->attach($roles->pluck('id'));

        expect($user->roles()->count())->toBe(3);
        expect($user->hasRole($roles->first()->name))->toBeTrue();
    });

    it('can check user permissions through roles', function () {
        $user = User::factory()->create();
        $role = Role::factory()->create(['name' => 'finance_manager']);
        $permission = Permission::factory()->create(['name' => 'view_financial_reports']);

        $role->permissions()->attach($permission->id);
        $user->roles()->attach($role->id);

        expect($user->can('view_financial_reports'))->toBeTrue();
        expect($user->can('delete_students'))->toBeFalse();
    });

    it('can enforce branch-level access control', function () {
        $branch1 = Branch::factory()->create();
        $branch2 = Branch::factory()->create();

        $user = User::factory()->create(['branch_id' => $branch1->id]);
        $student1 = Student::factory()->create(['branch_id' => $branch1->id]);
        $student2 = Student::factory()->create(['branch_id' => $branch2->id]);

        // User should be able to access students in their branch
        expect($user->can('view', $student1))->toBeTrue();
        
        // User should not be able to access students in other branches
        expect($user->can('view', $student2))->toBeFalse();
    });

    it('can handle organization-level permissions', function () {
        $branch1 = Branch::factory()->create();
        $branch2 = Branch::factory()->create();

        $admin = User::factory()->create(['branch_id' => $branch1->id]);
        $adminRole = Role::factory()->create(['name' => 'organization_admin', 'is_organization_level' => true]);
        
        $admin->roles()->attach($adminRole->id);

        $student1 = Student::factory()->create(['branch_id' => $branch1->id]);
        $student2 = Student::factory()->create(['branch_id' => $branch2->id]);

        // Organization admin should be able to access students in all branches
        expect($admin->can('view', $student1))->toBeTrue();
        expect($admin->can('view', $student2))->toBeTrue();
    });

    it('can prevent unauthorized invoice creation', function () {
        $branch = Branch::factory()->create();
        $student = Student::factory()->create(['branch_id' => $branch->id]);

        $teacher = User::factory()->create(['branch_id' => $branch->id]);
        $teacherRole = Role::factory()->create(['name' => 'teacher']);
        $teacher->roles()->attach($teacherRole->id);

        // Teacher should not be able to create invoices
        expect($teacher->can('create', Invoice::class))->toBeFalse();

        $financeUser = User::factory()->create(['branch_id' => $branch->id]);
        $financeRole = Role::factory()->create(['name' => 'finance_manager']);
        $financePermission = Permission::factory()->create(['name' => 'create_invoices']);
        $financeRole->permissions()->attach($financePermission->id);
        $financeUser->roles()->attach($financeRole->id);

        // Finance manager should be able to create invoices
        expect($financeUser->can('create', Invoice::class))->toBeTrue();
    });

    it('can prevent unauthorized payment processing', function () {
        $branch = Branch::factory()->create();
        $student = Student::factory()->create(['branch_id' => $branch->id]);
        $invoice = Invoice::factory()->create([
            'student_id' => $student->id,
            'branch_id' => $branch->id,
            'status' => 'issued',
        ]);

        $receptionist = User::factory()->create(['branch_id' => $branch->id]);
        $receptionistRole = Role::factory()->create(['name' => 'receptionist']);
        $receptionist->roles()->attach($receptionistRole->id);

        // Receptionist should not be able to process payments
        expect($receptionist->can('processPayment', $invoice))->toBeFalse();

        $financeUser = User::factory()->create(['branch_id' => $branch->id]);
        $financeRole = Role::factory()->create(['name' => 'finance_manager']);
        $financePermission = Permission::factory()->create(['name' => 'process_payments']);
        $financeRole->permissions()->attach($financePermission->id);
        $financeUser->roles()->attach($financeRole->id);

        // Finance manager should be able to process payments
        expect($financeUser->can('processPayment', $invoice))->toBeTrue();
    });

    it('can prevent teachers from accessing other teachers salary information', function () {
        $branch = Branch::factory()->create();
        $teacher1 = Teacher::factory()->create(['branch_id' => $branch->id]);
        $teacher2 = Teacher::factory()->create(['branch_id' => $branch->id]);

        $user1 = User::factory()->create(['branch_id' => $branch->id, 'teacher_id' => $teacher1->id]);
        $teacherRole = Role::factory()->create(['name' => 'teacher']);
        $user1->roles()->attach($teacherRole->id);

        // Teacher should be able to view their own salary
        expect($user1->can('viewSalary', $teacher1))->toBeTrue();
        
        // Teacher should not be able to view other teacher's salary
        expect($user1->can('viewSalary', $teacher2))->toBeFalse();
    });

    it('can prevent students from accessing other students records', function () {
        $branch = Branch::factory()->create();
        $student1 = Student::factory()->create(['branch_id' => $branch->id]);
        $student2 = Student::factory()->create(['branch_id' => $branch->id]);

        $user1 = User::factory()->create(['branch_id' => $branch->id, 'student_id' => $student1->id]);
        $studentRole = Role::factory()->create(['name' => 'student']);
        $user1->roles()->attach($studentRole->id);

        // Student should be able to view their own record
        expect($user1->can('view', $student1))->toBeTrue();
        
        // Student should not be able to view other student's record
        expect($user1->can('view', $student2))->toBeFalse();
    });

    it('can enforce class-level access for teachers', function () {
        $branch = Branch::factory()->create();
        $teacher1 = Teacher::factory()->create(['branch_id' => $branch->id]);
        $teacher2 = Teacher::factory()->create(['branch_id' => $branch->id]);

        $class1 = AcademicClass::factory()->create([
            'branch_id' => $branch->id,
            'teacher_id' => $teacher1->id,
        ]);

        $class2 = AcademicClass::factory()->create([
            'branch_id' => $branch->id,
            'teacher_id' => $teacher2->id,
        ]);

        $user1 = User::factory()->create(['branch_id' => $branch->id, 'teacher_id' => $teacher1->id]);
        $teacherRole = Role::factory()->create(['name' => 'teacher']);
        $user1->roles()->attach($teacherRole->id);

        // Teacher should be able to manage their own class
        expect($user1->can('update', $class1))->toBeTrue();
        expect($user1->can('markAttendance', $class1))->toBeTrue();
        
        // Teacher should not be able to manage other teacher's class
        expect($user1->can('update', $class2))->toBeFalse();
        expect($user1->can('markAttendance', $class2))->toBeFalse();
    });

    it('can prevent editing paid invoices', function () {
        $branch = Branch::factory()->create();
        $student = Student::factory()->create(['branch_id' => $branch->id]);
        $invoice = Invoice::factory()->create([
            'student_id' => $student->id,
            'branch_id' => $branch->id,
            'status' => 'paid',
        ]);

        $financeUser = User::factory()->create(['branch_id' => $branch->id]);
        $financeRole = Role::factory()->create(['name' => 'finance_manager']);
        $financePermission = Permission::factory()->create(['name' => 'edit_invoices']);
        $financeRole->permissions()->attach($financePermission->id);
        $financeUser->roles()->attach($financeRole->id);

        // Even finance manager should not be able to edit paid invoices
        expect($financeUser->can('update', $invoice))->toBeFalse();
    });

    it('can prevent deleting classes with active enrollments', function () {
        $branch = Branch::factory()->create();
        $class = AcademicClass::factory()->create(['branch_id' => $branch->id]);
        $students = Student::factory()->count(3)->create(['branch_id' => $branch->id]);

        foreach ($students as $student) {
            Enrollment::create([
                'student_id' => $student->id,
                'class_id' => $class->id,
                'enrollment_date' => now()->toDateString(),
                'status' => 'active',
            ]);
        }

        $admin = User::factory()->create(['branch_id' => $branch->id]);
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $admin->roles()->attach($adminRole->id);

        // Admin should not be able to delete class with active enrollments
        expect($admin->can('delete', $class))->toBeFalse();
    });
});

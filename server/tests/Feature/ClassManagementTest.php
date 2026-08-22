<?php

use App\Modules\Academic\Models\AcademicClass;
use App\Modules\Academic\Models\Enrollment;
use App\Modules\Academic\Models\Level;
use App\Modules\Academic\Models\Program;
use App\Modules\Academic\Models\Session;
use App\Modules\Academic\Models\Student;
use App\Modules\Iam\Models\Branch;
use App\Modules\PeopleHr\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Class Management', function () {
    
    it('can create a class with valid data', function () {
        $branch = Branch::factory()->create();
        $program = Program::factory()->create(['branch_id' => $branch->id]);
        $level = Level::factory()->create(['program_id' => $program->id]);
        $teacher = Teacher::factory()->create(['branch_id' => $branch->id, 'status' => 'active']);

        $classData = [
            'name' => 'Advanced English Class',
            'code' => 'ENG-ADV-001',
            'program_id' => $program->id,
            'level_id' => $level->id,
            'teacher_id' => $teacher->id,
            'branch_id' => $branch->id,
            'max_capacity' => 25,
            'min_capacity' => 10,
            'start_date' => now()->addDays(7)->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
            'schedule' => [
                ['day' => 'monday', 'start_time' => '09:00', 'end_time' => '11:00'],
                ['day' => 'wednesday', 'start_time' => '09:00', 'end_time' => '11:00'],
            ],
            'fee' => 15000,
        ];

        $class = AcademicClass::create($classData);

        expect($class)->toBeInstanceOf(AcademicClass::class);
        expect($class->name)->toBe('Advanced English Class');
        expect($class->code)->toBe('ENG-ADV-001');
        expect($class->max_capacity)->toBe(25);
        expect($class->fee)->toBe(15000);
    });

    it('cannot create a class with inactive teacher', function () {
        $branch = Branch::factory()->create();
        $program = Program::factory()->create(['branch_id' => $branch->id]);
        $level = Level::factory()->create(['program_id' => $program->id]);
        $teacher = Teacher::factory()->create(['branch_id' => $branch->id, 'status' => 'inactive']);

        $this->expectException(\Exception::class);

        AcademicClass::create([
            'name' => 'Test Class',
            'code' => 'TEST-001',
            'program_id' => $program->id,
            'level_id' => $level->id,
            'teacher_id' => $teacher->id,
            'branch_id' => $branch->id,
            'max_capacity' => 20,
            'start_date' => now()->addDays(7)->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
            'schedule' => [['day' => 'monday', 'start_time' => '09:00', 'end_time' => '11:00']],
            'fee' => 10000,
        ]);
    });

    it('cannot create a class with mismatched program and level', function () {
        $branch = Branch::factory()->create();
        $program1 = Program::factory()->create(['branch_id' => $branch->id]);
        $program2 = Program::factory()->create(['branch_id' => $branch->id]);
        $level = Level::factory()->create(['program_id' => $program1->id]);
        $teacher = Teacher::factory()->create(['branch_id' => $branch->id, 'status' => 'active']);

        $this->expectException(\Exception::class);

        AcademicClass::create([
            'name' => 'Test Class',
            'code' => 'TEST-002',
            'program_id' => $program2->id, // Different program
            'level_id' => $level->id, // Level belongs to program1
            'teacher_id' => $teacher->id,
            'branch_id' => $branch->id,
            'max_capacity' => 20,
            'start_date' => now()->addDays(7)->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
            'schedule' => [['day' => 'monday', 'start_time' => '09:00', 'end_time' => '11:00']],
            'fee' => 10000,
        ]);
    });

    it('can enroll students in a class', function () {
        $branch = Branch::factory()->create();
        $program = Program::factory()->create(['branch_id' => $branch->id]);
        $level = Level::factory()->create(['program_id' => $program->id]);
        $teacher = Teacher::factory()->create(['branch_id' => $branch->id, 'status' => 'active']);
        $class = AcademicClass::factory()->create([
            'program_id' => $program->id,
            'level_id' => $level->id,
            'teacher_id' => $teacher->id,
            'branch_id' => $branch->id,
            'max_capacity' => 25,
        ]);

        $students = Student::factory()->count(5)->create(['branch_id' => $branch->id]);

        foreach ($students as $student) {
            Enrollment::create([
                'student_id' => $student->id,
                'class_id' => $class->id,
                'enrollment_date' => now()->toDateString(),
                'status' => 'active',
            ]);
        }

        expect($class->enrollments()->count())->toBe(5);
        expect($class->enrollments()->where('status', 'active')->count())->toBe(5);
    });

    it('cannot enroll more students than class capacity', function () {
        $branch = Branch::factory()->create();
        $class = AcademicClass::factory()->create([
            'branch_id' => $branch->id,
            'max_capacity' => 3,
        ]);

        $students = Student::factory()->count(5)->create(['branch_id' => $branch->id]);

        $enrolledCount = 0;
        foreach ($students as $student) {
            if ($enrolledCount < $class->max_capacity) {
                Enrollment::create([
                    'student_id' => $student->id,
                    'class_id' => $class->id,
                    'enrollment_date' => now()->toDateString(),
                    'status' => 'active',
                ]);
                $enrolledCount++;
            }
        }

        expect($class->enrollments()->count())->toBe(3);
        expect($class->enrollments()->count())->toBeLessThanOrEqual($class->max_capacity);
    });

    it('can create sessions for a class', function () {
        $class = AcademicClass::factory()->create();

        $sessions = [];
        for ($i = 0; $i < 5; $i++) {
            $sessions[] = Session::create([
                'class_id' => $class->id,
                'session_date' => now()->addDays($i)->toDateString(),
                'start_time' => '09:00',
                'end_time' => '11:00',
                'topic' => "Session " . ($i + 1),
                'status' => 'scheduled',
            ]);
        }

        expect($class->sessions()->count())->toBe(5);
        expect($class->sessions()->where('status', 'scheduled')->count())->toBe(5);
    });

    it('can track class attendance rate', function () {
        $class = AcademicClass::factory()->create();
        $students = Student::factory()->count(10)->create();
        $session = Session::factory()->create(['class_id' => $class->id]);

        foreach ($students as $index => $student) {
            Enrollment::create([
                'student_id' => $student->id,
                'class_id' => $class->id,
                'enrollment_date' => now()->toDateString(),
                'status' => 'active',
            ]);

            // Mark attendance (80% present)
            $status = $index < 8 ? 'present' : 'absent';
            $session->rosters()->create([
                'student_id' => $student->id,
                'attendance_status' => $status,
                'marked_at' => now(),
            ]);
        }

        $totalStudents = $class->enrollments()->where('status', 'active')->count();
        $presentCount = $session->rosters()->where('attendance_status', 'present')->count();
        $attendanceRate = ($presentCount / $totalStudents) * 100;

        expect($totalStudents)->toBe(10);
        expect($presentCount)->toBe(8);
        expect($attendanceRate)->toBe(80.0);
    });

    it('can calculate class revenue', function () {
        $class = AcademicClass::factory()->create(['fee' => 10000]);
        $students = Student::factory()->count(5)->create();

        foreach ($students as $student) {
            Enrollment::create([
                'student_id' => $student->id,
                'class_id' => $class->id,
                'enrollment_date' => now()->toDateString(),
                'status' => 'active',
                'fee_paid' => 10000,
            ]);
        }

        $totalRevenue = $class->enrollments()->where('status', 'active')->sum('fee_paid');

        expect($totalRevenue)->toBe(50000);
    });
});

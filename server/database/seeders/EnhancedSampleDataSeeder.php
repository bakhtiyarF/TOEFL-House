<?php

namespace Database\Seeders;

use App\Modules\Academic\Models\Student;
use App\Modules\Academic\Models\AcademicClass;
use App\Modules\Academic\Models\Session;
use App\Modules\Academic\Models\Roster;
use App\Modules\Academic\Models\StudentJourneyEvent;
use App\Modules\FinancePayroll\Models\Payment;
use App\Modules\Iam\Models\Branch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Enhanced Sample Data Seeder
 *
 * Creates comprehensive, realistic sample data for development and testing.
 * Includes students, classes, sessions, attendance, payments, and journey events.
 */
class EnhancedSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding enhanced sample data...');

        $branch = Branch::first();
        if (!$branch) {
            $this->command->error('No branch found. Run IamSeeder first.');
            return;
        }

        // Create students with varied statuses
        $this->createStudents($branch);

        // Create classes with sessions
        $this->createClassesWithSessions($branch);

        // Create payments
        $this->createPayments($branch);

        // Create journey events
        $this->createJourneyEvents();

        $this->command->info('Enhanced sample data seeded successfully!');
    }

    private function createStudents(Branch $branch): void
    {
        $students = [
            ['full_name' => 'Ahmad Rahimi', 'gender' => 'male', 'phone' => '+93 700 123 456', 'status' => 'active'],
            ['full_name' => 'Fatima Ahmadi', 'gender' => 'female', 'phone' => '+93 700 234 567', 'status' => 'active'],
            ['full_name' => 'Mohammad Karimi', 'gender' => 'male', 'phone' => '+93 700 345 678', 'status' => 'active'],
            ['full_name' => 'Zahra Noori', 'gender' => 'female', 'phone' => '+93 700 456 789', 'status' => 'active'],
            ['full_name' => 'Ali Hussaini', 'gender' => 'male', 'phone' => '+93 700 567 890', 'status' => 'active'],
            ['full_name' => 'Sara Mohammadi', 'gender' => 'female', 'phone' => '+93 700 678 901', 'status' => 'active'],
            ['full_name' => 'Hassan Rezai', 'gender' => 'male', 'phone' => '+93 700 789 012', 'status' => 'active'],
            ['full_name' => 'Maryam Faizi', 'gender' => 'female', 'phone' => '+93 700 890 123', 'status' => 'graduated'],
            ['full_name' => 'Reza Ahmadi', 'gender' => 'male', 'phone' => '+93 700 901 234', 'status' => 'inactive'],
            ['full_name' => 'Nadia Faizi', 'gender' => 'female', 'phone' => '+93 700 012 345', 'status' => 'active'],
            ['full_name' => 'Farid Noor', 'gender' => 'male', 'phone' => '+93 700 111 222', 'status' => 'active'],
            ['full_name' => 'Zainab Hussaini', 'gender' => 'female', 'phone' => '+93 700 222 333', 'status' => 'active'],
            ['full_name' => 'Omid Safi', 'gender' => 'male', 'phone' => '+93 700 333 444', 'status' => 'active'],
            ['full_name' => 'Laila Nazari', 'gender' => 'female', 'phone' => '+93 700 444 555', 'status' => 'active'],
            ['full_name' => 'Wali Ahmadi', 'gender' => 'male', 'phone' => '+93 700 555 666', 'status' => 'suspended'],
        ];

        foreach ($students as $index => $studentData) {
            Student::create([
                'student_code' => sprintf('STU-2026-%04d', $index + 1),
                'full_name' => $studentData['full_name'],
                'gender' => $studentData['gender'],
                'phone' => $studentData['phone'],
                'father_name' => 'Father of ' . explode(' ', $studentData['full_name'])[0],
                'status' => $studentData['status'],
                'registration_date' => now()->subMonths(rand(1, 6)),
                'discount_percent' => $index % 3 === 0 ? rand(5, 20) : 0,
                'branch_id' => $branch->id,
                'placement_score' => ['score' => rand(60, 95), 'feePaid' => true],
            ]);
        }

        $this->command->info('Created ' . count($students) . ' students');
    }

    private function createClassesWithSessions(Branch $branch): void
    {
        $classNames = [
            ['name' => 'General English - Level 1', 'level' => 'L1', 'fee' => 5000],
            ['name' => 'General English - Level 2', 'level' => 'L2', 'fee' => 5500],
            ['name' => 'General English - Level 3', 'level' => 'L3', 'fee' => 6000],
            ['name' => 'TOEFL Preparation', 'level' => 'Advanced', 'fee' => 8000],
            ['name' => 'IELTS Advanced', 'level' => 'Advanced', 'fee' => 8500],
        ];

        foreach ($classNames as $classData) {
            $class = AcademicClass::create([
                'name' => $classData['name'],
                'level' => $classData['level'],
                'capacity' => 20,
                'min_viable_size' => 5,
                'schedule_time' => 'Mon/Wed/Fri 10:00-12:00',
                'start_date' => now()->subMonths(2),
                'end_date' => now()->addMonths(4),
                'status' => 'active',
                'fee' => $classData['fee'],
                'branch_id' => $branch->id,
            ]);

            // Create 10 sessions for each class
            $this->createSessionsForClass($class);

            // Enroll random students
            $this->enrollStudentsInClass($class);
        }

        $this->command->info('Created ' . count($classNames) . ' classes with sessions');
    }

    private function createSessionsForClass(AcademicClass $class): void
    {
        $topics = [
            'Introduction and Course Overview',
            'Grammar Fundamentals',
            'Reading Comprehension Strategies',
            'Writing Skills Workshop',
            'Listening Practice',
            'Speaking and Pronunciation',
            'Vocabulary Building',
            'Mid-term Review',
            'Advanced Grammar',
            'Final Exam Preparation',
        ];

        for ($i = 0; $i < 10; $i++) {
            $sessionDate = now()->subDays(30 - ($i * 3));
            $isCompleted = $sessionDate->isPast();

            $session = Session::create([
                'class_id' => $class->id,
                'date' => $sessionDate,
                'start_time' => '10:00',
                'end_time' => '12:00',
                'topic' => $topics[$i],
                'status' => $isCompleted ? 'completed' : 'scheduled',
            ]);

            if ($isCompleted) {
                $this->createRosterForSession($session, $class);
            }
        }
    }

    private function createRosterForSession(Session $session, AcademicClass $class): void
    {
        $students = $class->students;

        foreach ($students as $student) {
            // Randomize attendance (85% present, 10% absent, 5% sick/leave)
            $rand = rand(1, 100);
            $status = match (true) {
                $rand <= 85 => 'present',
                $rand <= 95 => 'absent',
                $rand <= 98 => 'sick',
                default => 'leave',
            };

            Roster::create([
                'session_id' => $session->id,
                'student_id' => $student->id,
                'attendance_status' => $status,
                'marked_at' => $session->date->addHours(2),
            ]);
        }
    }

    private function enrollStudentsInClass(AcademicClass $class): void
    {
        $students = Student::active()
            ->inRandomOrder()
            ->limit(rand(8, 15))
            ->get();

        foreach ($students as $student) {
            DB::table('student_semesters')->insert([
                'id' => Str::uuid(),
                'student_id' => $student->id,
                'class_id' => $class->id,
                'semester_name' => 'Spring 2026',
                'status' => 'active',
                'enroll_date' => now()->subMonths(2),
                'fee_amount' => $class->fee,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function createPayments(Branch $branch): void
    {
        $students = Student::active()->get();

        foreach ($students as $student) {
            // Create 1-3 payments per student
            $paymentCount = rand(1, 3);

            for ($i = 0; $i < $paymentCount; $i++) {
                Payment::create([
                    'student_id' => $student->id,
                    'amount' => rand(1000, 5000),
                    'payment_method' => ['cash', 'card', 'bank_transfer'][rand(0, 2)],
                    'category' => 'tuition',
                    'status' => 'completed',
                    'date' => now()->subDays(rand(1, 60)),
                    'branch_id' => $branch->id,
                    'receipt_number' => 'RCP-' . strtoupper(Str::random(8)),
                ]);
            }
        }

        $this->command->info('Created payments for ' . $students->count() . ' students');
    }

    private function createJourneyEvents(): void
    {
        $students = Student::all();

        foreach ($students as $student) {
            // Registration event
            StudentJourneyEvent::create([
                'student_id' => $student->id,
                'event_type' => 'STUDENT_REGISTERED',
                'occurred_at' => $student->registration_date,
                'payload' => ['full_name' => $student->full_name],
                'actor_name' => 'System',
            ]);

            // Placement test event
            if ($student->placement_score) {
                StudentJourneyEvent::create([
                    'student_id' => $student->id,
                    'event_type' => 'PLACEMENT_TEST_RECORDED',
                    'occurred_at' => $student->registration_date->addDay(),
                    'payload' => ['score' => $student->placement_score['score']],
                    'actor_name' => 'Placement Officer',
                ]);
            }

            // Enrollment event
            $enrollment = $student->enrollments()->first();
            if ($enrollment) {
                StudentJourneyEvent::create([
                    'student_id' => $student->id,
                    'event_type' => 'ENROLLMENT_CREATED',
                    'occurred_at' => $enrollment->started_at ?? $student->registration_date->addDays(2),
                    'payload' => ['enrollment_id' => $enrollment->id],
                    'actor_name' => 'Registrar',
                ]);
            }

            // Payment events
            $payments = $student->payments;
            foreach ($payments as $payment) {
                StudentJourneyEvent::create([
                    'student_id' => $student->id,
                    'event_type' => 'PAYMENT_RECORDED',
                    'occurred_at' => $payment->date,
                    'payload' => [
                        'amount' => $payment->amount,
                        'method' => $payment->payment_method,
                    ],
                    'actor_name' => 'Finance',
                ]);
            }
        }

        $this->command->info('Created journey events for ' . $students->count() . ' students');
    }
}

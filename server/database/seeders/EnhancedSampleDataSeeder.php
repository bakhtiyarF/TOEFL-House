<?php

namespace Database\Seeders;

use App\Modules\Academic\Models\Student;
use App\Modules\Academic\Models\AcademicClass;
use App\Modules\Academic\Models\Session;
use App\Modules\Academic\Models\Roster;
use App\Modules\Academic\Models\StudentJourneyEvent;
use App\Modules\FinancePayroll\Models\Payment;
use App\Modules\Iam\Models\Branch;
use App\Modules\Academic\Models\Certificate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Enhanced Sample Data Seeder (v3.1 - full role testing)
 *
 * Comprehensive realistic data for all 10 roles.
 * Powers:
 * - Live dashboards & GenerativeQuickActions
 * - Promotion tab (exam avg + roster attendance)
 * - Certificates (3 templates + print/PDF)
 * - Donor manager (Funding/Impact/Ledger)
 * - CRM follow-ups
 * - Finance, Inventory, Teachers, etc.
 */
class EnhancedSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding enhanced sample data for full 10-role live testing...');

        $branch = Branch::first();
        if (!$branch) {
            $this->command->error('No branch found. Run IamSeeder first.');
            return;
        }

        $this->createStudents($branch);
        $this->createClassesWithSessions($branch);
        $this->createPayments($branch);
        $this->createJourneyEvents();
        $this->createCertificates($branch);
        $this->createDonationsAndCampaigns($branch);
        $this->createFollowups($branch);
        $this->createExamResultsForPromotion($branch);

        // Extra: a few more certificates for designer queue visibility
        $this->createExtraCertificatesForDesigner($branch);

        // Inventory books (10 spec) — live for Inventory module
        $this->createSampleBooks($branch);

        // Create 10 real sample users (one per role) so RoleSwitcher + hybrid auth works with live data
        $this->createSampleUsersForAllRoles($branch);

        // People-HR: Teachers + Employees + sample evaluations (06 spec full live data)
        $this->createTeachersAndEmployees($branch);
        $this->createSampleTeacherEvaluations($branch);
        $this->createSampleSalaryLedgers($branch);

        $this->command->info('✅ Enhanced sample data seeded successfully for all roles.');
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
            ['full_name' => 'Samira Karimi', 'gender' => 'female', 'phone' => '+93 700 666 777', 'status' => 'active'],
        ];

        foreach ($students as $index => $studentData) {
            Student::create([
                'student_code' => sprintf('STU-2026-%04d', $index + 1),
                'full_name' => $studentData['full_name'],
                'gender' => $studentData['gender'],
                'phone' => $studentData['phone'],
                'father_name' => 'Father of ' . explode(' ', $studentData['full_name'])[0],
                'status' => $studentData['status'],
                'registration_date' => now()->subMonths(rand(1, 8)),
                'discount_percent' => $index % 3 === 0 ? rand(5, 25) : 0,
                'branch_id' => $branch->id,
                'placement_score' => ['score' => rand(55, 96), 'feePaid' => true],
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

            $this->createSessionsForClass($class);
            $this->enrollStudentsInClass($class);
        }

        $this->command->info('Created ' . count($classNames) . ' classes with sessions + rosters');
    }

    private function createSessionsForClass(AcademicClass $class): void
    {
        $topics = [
            'Introduction and Course Overview', 'Grammar Fundamentals', 'Reading Comprehension',
            'Writing Skills Workshop', 'Listening Practice', 'Speaking and Pronunciation',
            'Vocabulary Building', 'Mid-term Review', 'Advanced Grammar', 'Final Exam Preparation',
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
            $rand = rand(1, 100);
            $status = match (true) {
                $rand <= 82 => 'present',
                $rand <= 93 => 'absent',
                $rand <= 97 => 'sick',
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
        $students = Student::active()->inRandomOrder()->limit(rand(9, 16))->get();

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
            for ($i = 0; $i < rand(1, 4); $i++) {
                Payment::create([
                    'student_id' => $student->id,
                    'amount' => rand(1200, 6500),
                    'payment_method' => ['cash', 'card', 'bank_transfer'][rand(0, 2)],
                    'category' => ['fee', 'book', 'exam'][rand(0, 2)],
                    'status' => 'completed',
                    'date' => now()->subDays(rand(1, 75)),
                    'branch_id' => $branch->id,
                    'receipt_number' => 'RCP-' . strtoupper(Str::random(8)),
                ]);
            }
        }
        $this->command->info('Created payments');
    }

    private function createJourneyEvents(): void
    {
        $students = Student::all();

        foreach ($students as $student) {
            StudentJourneyEvent::create([
                'student_id' => $student->id,
                'event_type' => 'STUDENT_REGISTERED',
                'occurred_at' => $student->registration_date,
                'payload' => ['full_name' => $student->full_name],
                'actor_name' => 'System',
            ]);

            if ($student->placement_score) {
                StudentJourneyEvent::create([
                    'student_id' => $student->id,
                    'event_type' => 'PLACEMENT_TEST_RECORDED',
                    'occurred_at' => $student->registration_date->addDay(),
                    'payload' => ['score' => $student->placement_score['score']],
                    'actor_name' => 'Placement Officer',
                ]);
            }

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

            foreach ($student->payments as $payment) {
                StudentJourneyEvent::create([
                    'student_id' => $student->id,
                    'event_type' => 'PAYMENT_RECORDED',
                    'occurred_at' => $payment->date,
                    'payload' => ['amount' => $payment->amount],
                    'actor_name' => 'Finance',
                ]);
            }
        }
    }

    private function createCertificates(Branch $branch): void
    {
        $students = Student::whereIn('status', ['active', 'graduated'])->take(7)->get();
        $templates = ['classic', 'modern', 'minimal'];

        foreach ($students as $i => $student) {
            Certificate::create([
                'id' => Str::uuid()->toString(),
                'student_id' => $student->id,
                'certificate_no' => 'CERT-2026-' . str_pad(120 + $i, 4, '0', STR_PAD_LEFT),
                'grade' => ['Pass', 'Merit', 'Distinction'][rand(0, 2)],
                'issue_date' => now()->subDays(rand(3, 50))->toDateString(),
                'branch_id' => $branch->id,
                'template' => $templates[$i % 3],
                'status' => 'issued',
            ]);

            $student->addJourneyEvent('CERTIFICATE_ISSUED', [
                'certificate_no' => 'CERT-2026-' . str_pad(120 + $i, 4, '0', STR_PAD_LEFT),
                'template' => $templates[$i % 3],
            ], null, 'Designer');
        }
        $this->command->info('Created certificates (live for designer + Certificate.Issue)');
    }

    private function createExtraCertificatesForDesigner(Branch $branch): void
    {
        $students = Student::active()->skip(4)->take(3)->get();

        foreach ($students as $i => $student) {
            Certificate::create([
                'id' => Str::uuid()->toString(),
                'student_id' => $student->id,
                'certificate_no' => 'CERT-2026-DES-' . (300 + $i),
                'grade' => 'Merit',
                'issue_date' => now()->subDays(2 + $i)->toDateString(),
                'branch_id' => $branch->id,
                'template' => 'modern',
                'status' => 'issued',
            ]);
        }
    }

    private function createDonationsAndCampaigns(Branch $branch): void
    {
        $donorData = [
            ['full_name' => 'Afghan Education Foundation', 'type' => 'ngo'],
            ['full_name' => 'Dr. Ahmad Wali', 'type' => 'individual'],
            ['full_name' => 'Global Learning Initiative', 'type' => 'organization'],
            ['full_name' => 'Kabul Women Empowerment Fund', 'type' => 'ngo'],
        ];

        $donorIds = [];
        foreach ($donorData as $d) {
            $id = Str::uuid()->toString();
            $donorIds[] = $id;
            DB::table('donors')->insert([
                'id' => $id, 'full_name' => $d['full_name'], 'type' => $d['type'],
                'country' => 'Afghanistan', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        DB::table('funding_campaigns')->insert([
            'id' => Str::uuid()->toString(),
            'name' => 'Winter Scholarship Fund 2026',
            'target_amount' => 650000,
            'raised_amount' => 438000,
            'status' => 'active',
            'start_date' => now()->subMonths(2)->toDateString(),
            'branch_id' => $branch->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('funding_campaigns')->insert([
            'id' => Str::uuid()->toString(),
            'name' => 'New Library & Digital Resources',
            'target_amount' => 280000,
            'raised_amount' => 280000,
            'status' => 'completed',
            'start_date' => now()->subMonths(4)->toDateString(),
            'branch_id' => $branch->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $donations = [
            ['donor_id' => $donorIds[0], 'amount' => 125000],
            ['donor_id' => $donorIds[1], 'amount' => 45000],
            ['donor_id' => $donorIds[2], 'amount' => 92000],
            ['donor_id' => $donorIds[3], 'amount' => 68000],
        ];

        foreach ($donations as $don) {
            DB::table('donations')->insert([
                'id' => Str::uuid()->toString(),
                'donor_id' => $don['donor_id'],
                'amount' => $don['amount'],
                'date' => now()->subDays(rand(2, 18))->toDateString(),
                'branch_id' => $branch->id,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // Impact metrics (donor_manager)
        DB::table('impact_metrics')->insert([
            'id' => Str::uuid()->toString(),
            'name' => 'Students Supported by Scholarships',
            'category' => 'education',
            'current_value' => 52,
            'target_value' => 75,
            'progress_percent' => 69,
            'branch_id' => $branch->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('impact_metrics')->insert([
            'id' => Str::uuid()->toString(),
            'name' => 'Total Funds Raised (Q3)',
            'category' => 'funding',
            'current_value' => 438000,
            'target_value' => 650000,
            'progress_percent' => 67,
            'branch_id' => $branch->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->command->info('Created rich donor + impact data (live for donor_manager)');
    }

    private function createFollowups(Branch $branch): void
    {
        $visitors = DB::table('visitors')->where('branch_id', $branch->id)->take(5)->get();

        foreach ($visitors as $visitor) {
            DB::table('followups')->insert([
                'id' => Str::uuid()->toString(),
                'visitor_id' => $visitor->id,
                'note' => 'Initial inquiry — interested in General English course.',
                'next_date' => now()->addDays(rand(1, 6))->toDateString(),
                'created_at' => now()->subDays(4),
                'updated_at' => now(),
            ]);

            DB::table('followups')->insert([
                'id' => Str::uuid()->toString(),
                'visitor_id' => $visitor->id,
                'note' => 'Placement test reminder sent.',
                'next_date' => now()->addDays(rand(2, 5))->toDateString(),
                'created_at' => now()->subDays(1),
                'updated_at' => now(),
            ]);
        }
        $this->command->info('Created follow-up records (live CRM)');
    }

    private function createExamResultsForPromotion(Branch $branch): void
    {
        $classes = AcademicClass::where('branch_id', $branch->id)->take(3)->get();

        foreach ($classes as $class) {
            $students = $class->students->take(6);

            $examId = Str::uuid()->toString();
            DB::table('exams')->insert([
                'id' => $examId,
                'class_id' => $class->id,
                'title' => 'Mid-term Assessment',
                'type' => 'midterm',
                'date' => now()->subDays(9)->toDateString(),
                'total_marks' => 100,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            foreach ($students as $student) {
                DB::table('exam_results')->insert([
                    'id' => Str::uuid()->toString(),
                    'exam_id' => $examId,
                    'student_id' => $student->id,
                    'score' => rand(52, 94),
                    'max_score' => 100,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
        $this->command->info('Added exam results for live PromotionService recommendations');
    }

    /**
     * Create 10 real users (one per role) so RoleSwitcher + live auth works perfectly.
     * Password for all: "password"
     */
    private function createSampleUsersForAllRoles(Branch $branch): void
    {
        $users = [
            ['username' => 'owner',       'full_name' => 'Ahmad Rahimi (Owner)',           'role' => 'owner'],
            ['username' => 'general_mgr', 'full_name' => 'Karim Hussaini (General Manager)', 'role' => 'general_manager'],
            ['username' => 'hod',         'full_name' => 'Dr. Fatima Noori (Head of Dept)',  'role' => 'head_of_department'],
            ['username' => 'finance_mgr', 'full_name' => 'Nasir Ahmadi (Finance Manager)',   'role' => 'finance_manager'],
            ['username' => 'reception',   'full_name' => 'Zahra Karimi (Receptionist)',      'role' => 'receptionist'],
            ['username' => 'counselor',   'full_name' => 'Maryam Safi (Counselor)',          'role' => 'counselor'],
            ['username' => 'teacher1',    'full_name' => 'Mr. Ali Rezai (Teacher)',          'role' => 'teacher'],
            ['username' => 'data_entry',  'full_name' => 'Samiullah (Data Entry)',           'role' => 'data_entry'],
            ['username' => 'designer',    'full_name' => 'Laila Nazari (Designer)',          'role' => 'designer'],
            ['username' => 'donor_mgr',   'full_name' => 'Reza Wali (Donor Manager)',        'role' => 'donor_manager'],
        ];

        foreach ($users as $u) {
            DB::table('users')->updateOrInsert(
                ['username' => $u['username']],
                [
                    'id' => Str::uuid()->toString(),
                    'username' => $u['username'],
                    'full_name' => $u['full_name'],
                    'email' => $u['username'] . '@toeflhouse.af',
                    'password' => \Illuminate\Support\Facades\Hash::make('password'),
                    'role' => $u['role'],
                    'branch_id' => $branch->id,
                    'is_active' => true,
                    'must_change_password' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('Created 10 sample users (one per role) — login with username + password');
    }

    private function createSampleBooks(Branch $branch): void
    {
        $books = [
            ['title' => 'English Grammar in Use', 'price' => 450, 'purchase_price' => 280, 'stock' => 42, 'is_chapter' => false],
            ['title' => 'TOEFL iBT Official Guide', 'price' => 1200, 'purchase_price' => 850, 'stock' => 18, 'is_chapter' => false],
            ['title' => 'IELTS Practice Tests', 'price' => 950, 'purchase_price' => 620, 'stock' => 25, 'is_chapter' => false],
            ['title' => 'Vocabulary Builder - Intermediate', 'price' => 380, 'purchase_price' => 210, 'stock' => 65, 'is_chapter' => false],
            ['title' => 'Reading Comprehension Pack (Ch. 1-5)', 'price' => 220, 'purchase_price' => 140, 'stock' => 12, 'is_chapter' => true],
            ['title' => 'Listening Skills Workbook', 'price' => 550, 'purchase_price' => 320, 'stock' => 8, 'is_chapter' => false],
            ['title' => 'Speaking Practice Chapters', 'price' => 310, 'purchase_price' => 180, 'stock' => 31, 'is_chapter' => true],
        ];

        foreach ($books as $book) {
            DB::table('books')->insert([
                'id' => Str::uuid()->toString(),
                'title' => $book['title'],
                'price' => $book['price'],
                'purchase_price' => $book['purchase_price'],
                'stock' => $book['stock'],
                'is_chapter' => $book['is_chapter'],
                'branch_id' => $branch->id,
                'entry_date' => now()->subDays(rand(10, 90))->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('Created ' . count($books) . ' live inventory books (for Inventory module + sales)');

        // Seed a few sample book sales (live data for sales history + finance sync)
        $sampleBooks = DB::table('books')->where('branch_id', $branch->id)->take(3)->get();
        foreach ($sampleBooks as $idx => $book) {
            $qty = rand(1, min(3, $book->stock));
            $net = $book->price * $qty;
            $saleId = Str::uuid()->toString();

            DB::table('books')->where('id', $book->id)->decrement('stock', $qty);

            DB::table('book_sales')->insert([
                'id' => $saleId,
                'book_id' => $book->id,
                'quantity' => $qty,
                'total_amount' => $book->price * $qty,
                'discount_amount' => 0,
                'net_amount' => $net,
                'payment_method' => ['cash', 'card', 'bank_transfer'][rand(0, 2)],
                'status' => 'completed',
                'date' => now()->subDays(rand(1, 12))->toDateString(),
                'customer_name' => ['Walk-in Student', 'Ahmad Rahimi', 'Fatima Ahmadi'][rand(0,2)],
                'student_id' => null,
                'branch_id' => $branch->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Mirror income transaction (same as BookSaleService)
            DB::table('financial_transactions')->insert([
                'id' => Str::uuid()->toString(),
                'type' => 'income',
                'category' => $book->is_chapter ? 'chapter' : 'book',
                'amount' => $net,
                'date' => now()->toDateString(),
                'description' => "Book sale: {$book->title} × {$qty} (seeded)",
                'reference_id' => $saleId,
                'operator_name' => 'Seeder',
                'branch_id' => $branch->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $this->command->info('Seeded sample live book sales (with financial_transactions + stock impact)');

        // Live donations for Funding & Impact (11 spec)
        $donorIds = DB::table('donors')->pluck('id')->toArray();
        $campaignIds = DB::table('funding_campaigns')->pluck('id')->toArray();

        if (count($donorIds) > 0) {
            $donations = [
                ['donor_id' => $donorIds[0], 'amount' => 125000, 'campaign_id' => $campaignIds[0] ?? null],
                ['donor_id' => $donorIds[1] ?? $donorIds[0], 'amount' => 45000, 'campaign_id' => null],
            ];
            foreach ($donations as $d) {
                $did = Str::uuid()->toString();
                DB::table('donations')->insert([
                    'id' => $did,
                    'donor_id' => $d['donor_id'],
                    'campaign_id' => $d['campaign_id'],
                    'amount' => $d['amount'],
                    'date' => now()->subDays(rand(3, 25))->toDateString(),
                    'restricted' => false,
                    'receipt_no' => 'DON-' . strtoupper(Str::random(6)),
                    'branch_id' => $branch->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Mirror financial tx + savings (shared income path)
                DB::table('financial_transactions')->insert([
                    'id' => Str::uuid()->toString(),
                    'type' => 'income',
                    'category' => 'donation',
                    'amount' => $d['amount'],
                    'date' => now()->toDateString(),
                    'description' => 'Donation (seeded)',
                    'reference_id' => $did,
                    'operator_name' => 'Seeder',
                    'branch_id' => $branch->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $savePct = (float)(DB::table('system_settings')->where('key', 'daily_saving_percent')->value('value') ?? 5);
                DB::table('system_settings')->where('key', 'saving_balance')->increment('value', $d['amount'] * ($savePct / 100));
            }
            $this->command->info('Seeded live donations + financial tx for Funding module');
        }
    }

    /**
     * People & HR — Teachers + Employees (06 spec live seeding)
     * Ensures TeachersPage + EmployeesPage have rich live data + evaluations.
     */
    private function createTeachersAndEmployees(Branch $branch): void
    {
        $teachers = [
            ['full_name' => 'Ahmad Karimi', 'salary_type' => 'hybrid', 'base_salary' => 15000, 'specialization' => 'TOEFL Writing'],
            ['full_name' => 'Sarah Noori', 'salary_type' => 'per_skill', 'base_salary' => 0, 'specialization' => 'IELTS Speaking'],
            ['full_name' => 'Karim Rahimi', 'salary_type' => 'fixed', 'base_salary' => 25000, 'specialization' => 'General English'],
            ['full_name' => 'Fatima Ahmadi', 'salary_type' => 'per_session', 'base_salary' => 0, 'specialization' => 'TOEFL Reading'],
            ['full_name' => 'Ali Hussaini', 'salary_type' => 'per_level', 'base_salary' => 0, 'specialization' => 'Business English'],
        ];

        foreach ($teachers as $t) {
            DB::table('teachers')->insert([
                'id' => Str::uuid()->toString(),
                ...$t,
                'status' => 'active',
                'branch_id' => $branch->id,
                'joined_date' => now()->subMonths(rand(6, 24))->toDateString(),
                'performance_score' => round(rand(35, 50) / 10, 1),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // Employees (non-teaching staff)
        $emps = [
            ['full_name' => 'Samiullah Khan', 'role' => 'Admin Assistant', 'base_salary' => 8000],
            ['full_name' => 'Zahra Rezai', 'role' => 'Reception Lead', 'base_salary' => 6500],
            ['full_name' => 'Omar Faizi', 'role' => 'IT Support', 'base_salary' => 9000],
        ];

        foreach ($emps as $e) {
            DB::table('employees')->insert([
                'id' => Str::uuid()->toString(),
                'full_name' => $e['full_name'],
                'role' => $e['role'],
                'base_salary' => $e['base_salary'],
                'status' => 'active',
                'branch_id' => $branch->id,
                'joined_date' => now()->subMonths(rand(3, 18))->toDateString(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $this->command->info('Created 5 teachers + 3 employees for People-HR (live)');
    }

    /**
     * Sample live teacher evaluations (06 spec)
     * Populates teacher_evaluations table + refreshes performance_score
     */
    private function createSampleTeacherEvaluations(Branch $branch): void
    {
        $teachers = DB::table('teachers')->where('branch_id', $branch->id)->get();
        if ($teachers->isEmpty()) return;

        $evaluatorId = DB::table('users')->where('username', 'hod')->value('id')
            ?? DB::table('users')->first()->id ?? '00000000-0000-0000-0000-000000000000';

        $criteriaOptions = ['teaching_quality', 'student_engagement', 'punctuality', 'curriculum_adherence', 'overall'];

        foreach ($teachers->take(3) as $t) {
            for ($i = 0; $i < 2; $i++) {
                $date = now()->subDays(rand(5, 60))->toDateString();
                $crit = $criteriaOptions[array_rand($criteriaOptions)];
                DB::table('teacher_evaluations')->insert([
                    'id' => Str::uuid()->toString(),
                    'teacher_id' => $t->id,
                    'evaluator_id' => $evaluatorId,
                    'date' => $date,
                    'score' => round(rand(35, 48) / 10, 1),
                    'criteria' => json_encode([$crit]),
                    'notes' => ['Strong classroom management', 'Excellent student feedback', 'Good lesson planning', 'Needs improvement in punctuality'][rand(0, 3)],
                    'created_at' => now(),
                ]);
            }
        }

        // Refresh performance_score from actual evals
        foreach ($teachers as $t) {
            $avg = DB::table('teacher_evaluations')->where('teacher_id', $t->id)->avg('score');
            if ($avg) {
                DB::table('teachers')->where('id', $t->id)->update(['performance_score' => round($avg, 2)]);
            }
        }

        $this->command->info('Created sample teacher evaluations (live in DB for TeachersPage)');
    }

    /**
     * Seed sample teacher salary ledger entries (for live salary-history UI in 06)
     */
    private function createSampleSalaryLedgers(Branch $branch): void
    {
        $teachers = DB::table('teachers')->where('branch_id', $branch->id)->get();
        if ($teachers->isEmpty()) return;

        $periods = [now()->subMonth()->format('Y-m'), now()->format('Y-m')];

        foreach ($teachers->take(3) as $t) {
            foreach ($periods as $p) {
                $due = rand(12000, 28000);
                $paid = rand(0, $due);
                DB::table('teacher_salary_ledger')->insert([
                    'id' => Str::uuid()->toString(),
                    'teacher_id' => $t->id,
                    'period_key' => $p,
                    'period_label' => strtoupper(date('M Y', strtotime($p . '-01'))),
                    'due_amount' => $due,
                    'paid_amount' => $paid,
                    'payment_type' => $paid >= $due ? 'full' : ($paid > 0 ? 'partial' : 'advance'),
                    'paid_at' => now()->subDays(rand(1, 20)),
                    'operator_name' => 'Finance Mgr',
                    'branch_id' => $branch->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('Seeded live salary ledger history for teachers (06)');
    }
}

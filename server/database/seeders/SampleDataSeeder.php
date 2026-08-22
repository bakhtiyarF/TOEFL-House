<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Sample Data Seeder
 *
 * Creates realistic demo data for all modules.
 * Only seeds when no real data exists (pre-launch system, 01 §3a).
 */
class SampleDataSeeder extends Seeder
{
    private string $orgId;
    private string $campusId;
    private array $branchIds = [];
    private array $userIds = [];

    public function run(): void
    {
        $this->seedOrganization();
        $this->seedUsers();
        $this->seedAcademic();
        $this->seedPeopleHR();
        $this->seedFinance();
        $this->seedInventory();
        $this->seedCRM();
        $this->seedFunding();

        $this->command->info('Sample data seeded successfully.');
    }

    private function seedOrganization(): void
    {
        $this->orgId = Str::uuid()->toString();
        DB::table('organizations')->insert([
            'id' => $this->orgId,
            'name' => 'TOEFL House',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->campusId = Str::uuid()->toString();
        DB::table('campuses')->insert([
            'id' => $this->campusId,
            'organization_id' => $this->orgId,
            'name' => 'Main Campus',
            'code' => 'MAIN',
            'address' => 'Kabul, Afghanistan',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $branches = [
            ['name' => 'Kabul Central', 'code' => 'KBL-01', 'location' => 'Kabul'],
            ['name' => 'Herat Branch', 'code' => 'HRT-01', 'location' => 'Herat'],
            ['name' => 'Mazar Branch', 'code' => 'MZR-01', 'location' => 'Mazar-i-Sharif'],
        ];

        foreach ($branches as $branch) {
            $id = Str::uuid()->toString();
            $this->branchIds[] = $id;
            DB::table('branches')->insert([
                'id' => $id,
                'campus_id' => $this->campusId,
                'name' => $branch['name'],
                'code' => $branch['code'],
                'location' => $branch['location'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedUsers(): void
    {
        $mainBranch = $this->branchIds[0];
        $users = [
            ['username' => 'admin', 'full_name' => 'Ahmad Rahimi', 'role' => 'owner'],
            ['username' => 'manager', 'full_name' => 'Karim Hussaini', 'role' => 'manager'],
            ['username' => 'finance', 'full_name' => 'Nasir Ahmadi', 'role' => 'finance'],
            ['username' => 'reception', 'full_name' => 'Zahra Noori', 'role' => 'registrar'],
            ['username' => 'teacher1', 'full_name' => 'Mr. Ahmed Karimi', 'role' => 'teacher'],
            ['username' => 'teacher2', 'full_name' => 'Ms. Sarah Noori', 'role' => 'teacher'],
            ['username' => 'counselor', 'full_name' => 'Fatima Ahmadi', 'role' => 'counselor'],
            ['username' => 'donor_mgr', 'full_name' => 'Reza Safi', 'role' => 'donor_manager'],
        ];

        foreach ($users as $user) {
            $id = Str::uuid()->toString();
            $this->userIds[] = $id;
            DB::table('users')->insert([
                'id' => $id,
                'username' => $user['username'],
                'password' => Hash::make('password'),
                'full_name' => $user['full_name'],
                'role' => $user['role'],
                'branch_id' => $mainBranch,
                'is_active' => true,
                'must_change_password' => false,
                'two_factor_enabled' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedAcademic(): void
    {
        $branchId = $this->branchIds[0];

        // Program
        $programId = Str::uuid()->toString();
        DB::table('programs')->insert([
            'id' => $programId, 'name' => 'General English', 'code' => 'GE',
            'duration_months' => 12, 'is_active' => true, 'branch_id' => $branchId,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Program Version
        $pvId = Str::uuid()->toString();
        DB::table('program_versions')->insert([
            'id' => $pvId, 'program_id' => $programId, 'version_label' => 'v1.0',
            'version_number' => 1, 'status' => 'published', 'is_default' => true,
            'published_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        // Levels
        $levelIds = [];
        foreach (['Level 1', 'Level 2', 'Level 3', 'Level 4', 'Level 5'] as $i => $name) {
            $lid = Str::uuid()->toString();
            $levelIds[] = $lid;
            DB::table('levels')->insert([
                'id' => $lid, 'program_id' => $programId, 'program_version_id' => $pvId,
                'name' => $name, 'code' => 'L' . ($i + 1), 'order' => $i + 1,
                'duration_months' => 3, 'default_fee' => 5000, 'pass_mark' => 60,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // Students
        $studentNames = [
            'Ahmad Rahimi', 'Fatima Ahmadi', 'Mohammad Karimi', 'Zahra Noori',
            'Ali Hussaini', 'Sara Mohammadi', 'Hassan Rezai', 'Maryam Faizi',
            'Reza Ahmadi', 'Nadia Faizi', 'Farid Noor', 'Zainab Hussaini',
        ];

        $studentIds = [];
        foreach ($studentNames as $i => $name) {
            $sid = Str::uuid()->toString();
            $studentIds[] = $sid;
            DB::table('students')->insert([
                'id' => $sid,
                'student_code' => sprintf('STU-2026-%04d', $i + 1),
                'full_name' => $name,
                'phone' => '+93 700 ' . str_pad(100 + $i, 3, '0', STR_PAD_LEFT) . ' ' . str_pad(100 + $i * 11, 3, '0', STR_PAD_LEFT),
                'gender' => $i % 2 === 0 ? 'male' : 'female',
                'father_name' => 'Father of ' . explode(' ', $name)[0],
                'status' => $i < 10 ? 'active' : ($i < 11 ? 'graduated' : 'inactive'),
                'registration_date' => now()->subMonths(rand(1, 6))->toDateString(),
                'discount_percent' => $i % 3 === 0 ? rand(5, 20) : 0,
                'branch_id' => $branchId,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // Classes
        $classNames = [
            'General English - Level 1', 'General English - Level 3',
            'TOEFL Preparation - Level 2', 'IELTS Advanced',
        ];

        foreach ($classNames as $i => $className) {
            $classId = Str::uuid()->toString();
            DB::table('classes')->insert([
                'id' => $classId, 'name' => $className,
                'level_id' => $levelIds[min($i, 4)],
                'capacity' => 20, 'min_viable_size' => 5,
                'schedule_time' => ['Sun/Tue 09:00-11:00', 'Mon/Wed 14:00-16:00', 'Sat 10:00-13:00', 'Tue/Thu 11:00-13:00'][$i],
                'start_date' => now()->subMonths(2)->toDateString(),
                'status' => $i < 3 ? 'active' : 'active',
                'fee' => 5000 + ($i * 1000),
                'gender_policy' => $i % 2 === 0 ? 'mixed' : 'female',
                'branch_id' => $branchId,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function seedPeopleHR(): void
    {
        $branchId = $this->branchIds[0];
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
                'branch_id' => $branchId,
                'joined_date' => now()->subMonths(rand(6, 24))->toDateString(),
                'performance_score' => round(rand(35, 50) / 10, 1),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function seedFinance(): void
    {
        $branchId = $this->branchIds[0];

        // Budget lines
        $budgetItems = [
            ['name' => 'Teacher Salaries', 'purpose' => 'teacher_salary', 'allocated' => 200000, 'spent' => 145000, 'type' => 'fixed'],
            ['name' => 'Office Rent', 'purpose' => 'rent', 'allocated' => 50000, 'spent' => 50000, 'type' => 'fixed'],
            ['name' => 'Utilities', 'purpose' => 'utilities', 'allocated' => 15000, 'spent' => 12000, 'type' => 'variable'],
            ['name' => 'Supplies', 'purpose' => 'supplies', 'allocated' => 10000, 'spent' => 7200, 'type' => 'variable'],
            ['name' => 'Marketing', 'purpose' => 'marketing', 'allocated' => 20000, 'spent' => 8500, 'type' => 'variable', 'is_marketing' => true],
        ];

        foreach ($budgetItems as $b) {
            DB::table('budget_lines')->insert([
                'id' => Str::uuid()->toString(),
                'name' => $b['name'], 'purpose' => $b['purpose'],
                'current_amount' => $b['spent'], 'allocated_amount' => $b['allocated'],
                'cost_type' => $b['type'], 'is_marketing' => $b['is_marketing'] ?? false,
                'branch_id' => $branchId,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // Transactions
        $transactions = [
            ['type' => 'income', 'category' => 'fee', 'amount' => 15000, 'desc' => 'Ahmad Rahimi — Semester fee'],
            ['type' => 'income', 'category' => 'fee', 'amount' => 5000, 'desc' => 'Zahra Noori — Registration'],
            ['type' => 'expense', 'category' => 'rent', 'amount' => 50000, 'desc' => 'Monthly office rent'],
            ['type' => 'income', 'category' => 'book', 'amount' => 3500, 'desc' => 'Book sale — TOEFL Guide'],
            ['type' => 'expense', 'category' => 'payroll', 'amount' => 45000, 'desc' => 'Teacher salaries — August'],
        ];

        foreach ($transactions as $i => $tx) {
            DB::table('financial_transactions')->insert([
                'id' => Str::uuid()->toString(),
                'type' => $tx['type'], 'category' => $tx['category'],
                'amount' => $tx['amount'], 'date' => now()->subDays($i)->toDateString(),
                'description' => $tx['desc'], 'operator_name' => 'Admin',
                'branch_id' => $branchId,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function seedInventory(): void
    {
        $branchId = $this->branchIds[0];
        $books = [
            ['title' => 'Official TOEFL Guide 5th Ed.', 'price' => 800, 'purchase_price' => 500, 'stock' => 45],
            ['title' => "Barron's TOEFL iBT", 'price' => 1200, 'purchase_price' => 750, 'stock' => 23],
            ['title' => 'Reading Skills - Level 1', 'price' => 250, 'purchase_price' => 150, 'stock' => 67, 'is_chapter' => true],
            ['title' => 'Writing Skills - Level 2', 'price' => 300, 'purchase_price' => 180, 'stock' => 34, 'is_chapter' => true],
            ['title' => 'Cambridge IELTS 17', 'price' => 900, 'purchase_price' => 600, 'stock' => 12],
            ['title' => 'Grammar in Use', 'price' => 650, 'purchase_price' => 400, 'stock' => 0],
        ];

        foreach ($books as $book) {
            DB::table('books')->insert([
                'id' => Str::uuid()->toString(),
                'title' => $book['title'], 'price' => $book['price'],
                'purchase_price' => $book['purchase_price'], 'stock' => $book['stock'],
                'is_chapter' => $book['is_chapter'] ?? false,
                'branch_id' => $branchId, 'entry_date' => now()->toDateString(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function seedCRM(): void
    {
        $branchId = $this->branchIds[0];
        $visitors = [
            ['name' => 'Omid Safi', 'stage' => 'lead', 'source' => 'friend'],
            ['name' => 'Laila Nazari', 'stage' => 'follow_up', 'source' => 'social'],
            ['name' => 'Wali Ahmadi', 'stage' => 'placement_completed', 'source' => 'ads', 'score' => 78],
            ['name' => 'Shabnam Rahimi', 'stage' => 'inquiry', 'source' => 'referral'],
            ['name' => 'Jawid Karimi', 'stage' => 'placement_booking', 'source' => 'organic'],
        ];

        foreach ($visitors as $v) {
            DB::table('visitors')->insert([
                'id' => Str::uuid()->toString(),
                'serial_no' => 'V-' . strtoupper(Str::random(6)),
                'full_name' => $v['name'], 'phone' => '+93 700 ' . rand(100, 999) . ' ' . rand(100, 999),
                'stage' => $v['stage'], 'source' => $v['source'],
                'status' => 'visited', 'visit_date' => now()->subDays(rand(1, 7))->toDateString(),
                'placement_score' => isset($v['score']) ? json_encode(['score' => $v['score'], 'feePaid' => true]) : null,
                'branch_id' => $branchId,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function seedFunding(): void
    {
        $branchId = $this->branchIds[0];

        // Donors
        $donors = [
            ['name' => 'Afghan Education Foundation', 'type' => 'ngo'],
            ['name' => 'Dr. Ahmad Wali', 'type' => 'individual'],
            ['name' => 'Global Learning Initiative', 'type' => 'organization'],
        ];

        $donorIds = [];
        foreach ($donors as $d) {
            $id = Str::uuid()->toString();
            $donorIds[] = $id;
            DB::table('donors')->insert([
                'id' => $id, 'full_name' => $d['name'], 'type' => $d['type'],
                'country' => 'Afghanistan', 'created_at' => now(),
            ]);
        }

        // Campaigns
        $campaigns = [
            ['name' => 'Winter Scholarship Fund', 'target' => 500000, 'raised' => 350000],
            ['name' => 'New Library Books', 'target' => 200000, 'raised' => 200000, 'status' => 'completed'],
            ['name' => 'Teacher Training Program', 'target' => 300000, 'raised' => 125000],
        ];

        foreach ($campaigns as $c) {
            DB::table('funding_campaigns')->insert([
                'id' => Str::uuid()->toString(),
                'name' => $c['name'], 'target_amount' => $c['target'],
                'raised_amount' => $c['raised'],
                'status' => $c['status'] ?? 'active',
                'start_date' => now()->subMonths(3)->toDateString(),
                'branch_id' => $branchId,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // Scholarships
        DB::table('scholarships')->insert([
            'id' => Str::uuid()->toString(),
            'name' => 'Merit Scholarship', 'total_budget' => 200000,
            'allocated_amount' => 145000, 'status' => 'active',
            'branch_id' => $branchId, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}

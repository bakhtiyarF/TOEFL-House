<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Rule Engine Default Rules Seeder
 * Seeds the 20 default rules from 02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md §7.4
 * Including rules 19–20 (payroll multiplier tiers, added in 02 §10 decision #2)
 * Excluding deprecated rules 8–9 (promotion flat rules, per 05 §5 decision #1)
 */
class RuleEngineSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            [
                'name' => 'Placement Test Fee — First Attempt',
                'category' => 'fee',
                'priority' => 100,
                'conditions' => [['field' => 'isFirstPlacementTest', 'operator' => '==', 'value' => true]],
                'actions' => [['type' => 'set_value', 'targetKey' => 'placementTestFee', 'value' => 300]],
            ],
            [
                'name' => 'Placement Test Fee — Retake',
                'category' => 'fee',
                'priority' => 100,
                'conditions' => [['field' => 'isFirstPlacementTest', 'operator' => '==', 'value' => false]],
                'actions' => [['type' => 'set_value', 'targetKey' => 'placementTestFee', 'value' => 0]],
            ],
            [
                'name' => 'Diploma / Certificate Issuance Fee',
                'category' => 'fee',
                'priority' => 90,
                'conditions' => [
                    ['field' => 'isFirstCertificate', 'operator' => '==', 'value' => true],
                    ['field' => 'examScore', 'operator' => '>=', 'value' => 90],
                ],
                'actions' => [['type' => 'set_value', 'targetKey' => 'diplomaFee', 'value' => 500]],
            ],
            [
                'name' => 'Smart ID Card Issuance Fee',
                'category' => 'fee',
                'priority' => 90,
                'conditions' => [['field' => 'isFirstCardIssuance', 'operator' => '==', 'value' => true]],
                'actions' => [['type' => 'set_value', 'targetKey' => 'cardIssuanceFee', 'value' => 200]],
            ],
            [
                'name' => 'Friend Referral Discount',
                'category' => 'discount',
                'priority' => 80,
                'conditions' => [['field' => 'leadSource', 'operator' => '==', 'value' => 'friend']],
                'actions' => [['type' => 'add_discount', 'value' => 10]],
            ],
            [
                'name' => 'Early Registration Discount',
                'category' => 'discount',
                'priority' => 70,
                'conditions' => [['field' => 'daysBeforeClassStart', 'operator' => '>=', 'value' => 14]],
                'actions' => [['type' => 'add_discount', 'value' => 5]],
            ],
            [
                'name' => 'Discount Cap Enforcement',
                'description' => 'Caps total discount at 30%. Per 02 §10 decision #1: preserved single-pass behavior.',
                'category' => 'discount',
                'priority' => 200,
                'conditions' => [['field' => 'discountPercent', 'operator' => '>', 'value' => 30]],
                'actions' => [
                    ['type' => 'set_value', 'targetKey' => 'discountPercent', 'value' => 30],
                    ['type' => 'warn', 'message' => 'Discount capped at the maximum allowable 30%.'],
                ],
            ],
            // Rules 8–9 (Promotion) are DEPRECATED per 05 §5 — not seeded
            [
                'name' => 'Attendance Warning — Below Threshold',
                'category' => 'attendance',
                'priority' => 100,
                'conditions' => [['field' => 'attendanceRate', 'operator' => '<', 'value' => 85]],
                'actions' => [
                    ['type' => 'warn', 'message' => 'Attendance below 85% threshold'],
                    ['type' => 'notify', 'channel' => 'sms', 'message' => 'Parent notification: low attendance'],
                ],
            ],
            [
                'name' => 'Attendance Critical — Risk of Exclusion',
                'category' => 'attendance',
                'priority' => 150,
                'conditions' => [['field' => 'attendanceRate', 'operator' => '<', 'value' => 60]],
                'actions' => [
                    ['type' => 'warn', 'message' => 'CRITICAL: Attendance below 60% — risk of exclusion from exams'],
                ],
            ],
            [
                'name' => 'Automatic Savings — 5% of Income',
                'category' => 'finance',
                'priority' => 100,
                'conditions' => [
                    ['field' => 'transactionType', 'operator' => '==', 'value' => 'income'],
                    ['field' => 'amount', 'operator' => '>', 'value' => 0],
                ],
                'actions' => [
                    ['type' => 'calculate', 'targetKey' => 'savingAmount', 'formula' => 'amount * 0.05'],
                ],
            ],
            [
                'name' => 'Profit Withdrawal Block — Reserve Fund Incomplete',
                'category' => 'finance',
                'priority' => 200,
                'conditions' => [['field' => 'reserveFundMet', 'operator' => '==', 'value' => false]],
                'actions' => [
                    ['type' => 'block', 'reason' => 'Reserve fund (6-month target) has not been met. Profit withdrawal blocked.'],
                ],
            ],
            [
                'name' => 'Profit Withdrawal Tier — 10%',
                'category' => 'finance',
                'priority' => 100,
                'conditions' => [
                    ['field' => 'reserveFundMet', 'operator' => '==', 'value' => true],
                    ['field' => 'profitMargin', 'operator' => 'between', 'value' => [10, 20]],
                ],
                'actions' => [['type' => 'set_value', 'targetKey' => 'withdrawablePercent', 'value' => 10]],
            ],
            [
                'name' => 'Profit Withdrawal Tier — 15%',
                'category' => 'finance',
                'priority' => 100,
                'conditions' => [
                    ['field' => 'reserveFundMet', 'operator' => '==', 'value' => true],
                    ['field' => 'profitMargin', 'operator' => 'between', 'value' => [20, 30]],
                ],
                'actions' => [['type' => 'set_value', 'targetKey' => 'withdrawablePercent', 'value' => 15]],
            ],
            [
                'name' => 'Profit Withdrawal Tier — 20%',
                'category' => 'finance',
                'priority' => 100,
                'conditions' => [
                    ['field' => 'reserveFundMet', 'operator' => '==', 'value' => true],
                    ['field' => 'profitMargin', 'operator' => '>', 'value' => 30],
                ],
                'actions' => [['type' => 'set_value', 'targetKey' => 'withdrawablePercent', 'value' => 20]],
            ],
            [
                'name' => 'Minimum Class Size Warning',
                'category' => 'academic',
                'priority' => 100,
                'conditions' => [
                    ['field' => 'enrolledCount', 'operator' => '<', 'value' => 6],
                    ['field' => 'classStatus', 'operator' => '==', 'value' => 'active'],
                ],
                'actions' => [
                    ['type' => 'warn', 'message' => 'Class enrollment below minimum viable size (6). Consider merging with another class.'],
                ],
            ],
            [
                'name' => 'Per-Skill Salary Calculation',
                'category' => 'payroll',
                'priority' => 100,
                'conditions' => [['field' => 'salaryType', 'operator' => '==', 'value' => 'per_skill']],
                'actions' => [
                    ['type' => 'calculate', 'targetKey' => 'monthlySalary', 'formula' => 'totalSkillRates'],
                ],
            ],
            // Rules 19–20: NEW in v3, per 02 §10 decision #2
            [
                'name' => 'Class Payroll Multiplier — Below Minimum Size',
                'description' => 'Applied when class has fewer than 6 enrolled students. 0.75 multiplier — confirm with compensation policy before first real payroll run.',
                'category' => 'payroll',
                'priority' => 100,
                'conditions' => [['field' => 'enrolledCount', 'operator' => '<', 'value' => 6]],
                'actions' => [
                    ['type' => 'set_value', 'targetKey' => 'payrollMultiplier', 'value' => 0.75],
                    ['type' => 'set_value', 'targetKey' => 'payrollTier', 'value' => 'below_minimum'],
                ],
            ],
            [
                'name' => 'Class Payroll Multiplier — Standard',
                'description' => 'Applied when class has 6 or more enrolled students.',
                'category' => 'payroll',
                'priority' => 100,
                'conditions' => [['field' => 'enrolledCount', 'operator' => '>=', 'value' => 6]],
                'actions' => [
                    ['type' => 'set_value', 'targetKey' => 'payrollMultiplier', 'value' => 1.0],
                    ['type' => 'set_value', 'targetKey' => 'payrollTier', 'value' => 'standard'],
                ],
            ],
        ];

        foreach ($rules as $rule) {
            DB::table('rule_definitions')->insert([
                'id' => Str::uuid()->toString(),
                'name' => $rule['name'],
                'description' => $rule['description'] ?? null,
                'category' => $rule['category'],
                'conditions' => json_encode($rule['conditions']),
                'actions' => json_encode($rule['actions']),
                'priority' => $rule['priority'],
                'is_active' => true,
                'scope_branch_id' => null, // org-wide
                'version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('Seeded ' . count($rules) . ' default rule definitions.');

        // Seed essential system_settings (cross-module, used by Finance 07, Platform 08)
        $settings = [
            'daily_saving_percent' => '5',
            'saving_balance' => '125000',
            'main_account_balance' => '890000',
            'reserve_fund_target_months' => '6',
            'min_class_size' => '6',
            'default_currency' => 'AFN',
            'timezone' => 'Asia/Kabul',
            'attendance_warning_threshold' => '85',
            'attendance_critical_threshold' => '60',
        ];

        foreach ($settings as $key => $value) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('Seeded ' . count($settings) . ' system_settings keys (live for Finance + Platform).');

        // Seed sample notifications for live UI testing (Platform 08)
        $branchId = DB::table('branches')->value('id');
        $sampleNotifications = [
            ['title' => 'New Student Registered', 'message' => 'Ahmad Rahimi has been registered.', 'type' => 'success'],
            ['title' => 'Payment Received', 'message' => '4,500 AFN payment recorded.', 'type' => 'info'],
            ['title' => 'Attendance Warning', 'message' => 'General English - Level 1: attendance at 78% — below 85% threshold.', 'type' => 'warning'],
            ['title' => 'Expense Approval Needed', 'message' => 'New expense request for textbooks (12,000 AFN) pending.', 'type' => 'info'],
        ];
        foreach ($sampleNotifications as $n) {
            DB::table('notifications')->insertOrIgnore([
                'id' => Str::uuid()->toString(),
                'title' => $n['title'],
                'message' => $n['message'],
                'date' => now()->toDateString(),
                'read' => false,
                'type' => $n['type'],
                'user_id' => null,
                'branch_id' => $branchId,
                'created_at' => now()->subMinutes(rand(5, 180)),
                'updated_at' => now(),
            ]);
        }
        $this->command->info('Seeded sample live notifications.');

        // Seed a few audit logs (live /audit-logs)
        $adminUser = DB::table('users')->where('role', 'owner')->first();
        $auditLogs = [
            ['action' => 'Payment recorded', 'operator_name' => $adminUser?->full_name ?? 'System', 'old_value' => null, 'new_value' => ['amount' => 4500]],
            ['action' => 'Expense approved', 'operator_name' => 'Finance Manager', 'old_value' => ['status' => 'pending'], 'new_value' => ['status' => 'approved']],
            ['action' => 'Rule updated', 'operator_name' => $adminUser?->full_name ?? 'System', 'old_value' => null, 'new_value' => ['priority' => 200]],
            ['action' => 'Certificate issued', 'operator_name' => 'Designer', 'old_value' => null, 'new_value' => ['certificate_no' => 'CERT-2026-0124']],
        ];
        foreach ($auditLogs as $log) {
            DB::table('audit_logs')->insert([
                'id' => Str::uuid()->toString(),
                'operator_id' => $adminUser?->id ?? null,
                'operator_name' => $log['operator_name'],
                'action' => $log['action'],
                'date' => now()->toDateString(),
                'time' => now()->format('H:i:s'),
                'old_value' => $log['old_value'] ? json_encode($log['old_value']) : null,
                'new_value' => $log['new_value'] ? json_encode($log['new_value']) : null,
                'ip' => '127.0.0.1',
                'device' => 'Desktop',
                'branch_id' => $branchId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $this->command->info('Seeded sample live audit logs.');
    }
}

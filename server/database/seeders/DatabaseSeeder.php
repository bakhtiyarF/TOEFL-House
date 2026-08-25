<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Main Database Seeder
 * Runs in correct order:
 * 1. IAM (roles + permissions per 02 §5)
 * 2. Enhanced sample data (students, classes, rosters, payments, certificates, donations, followups, exam results)
 *    → Powers live dashboards, GenerativeQuickActions, promotions, certificates (print/PDF), donor_manager, CRM
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            IamSeeder::class,                    // Critical: 10 roles + permissions (owner exclusions etc.)
            EnhancedSampleDataSeeder::class,     // Rich realistic data for all modules (live everywhere)
            RuleEngineSeeder::class,             // 20 default rule definitions (02 §7 + 08 Platform)
        ]);

        $this->command->info('✅ Full TOEFL House v3 database seeded with live data for all 10 roles.');
        $this->command->info('   Includes: students, classes+rosters, attendance, payments, journey, certificates (3 templates), donations/campaigns/impact, follow-ups, exam results (for PromotionService).');
        $this->command->info('   + Platform Services: RuleEngine (20 rules), system_settings seeds.');
    }
}

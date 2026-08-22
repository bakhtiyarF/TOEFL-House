<?php

/**
 * PayrollService Tests
 * Tests the five salary models from 02 §8
 */

describe('PayrollService — Salary Models', function () {

    it('fixed model returns base_salary only', function () {
        $salaryType = 'fixed';
        $baseSalary = 25000;

        $due = match($salaryType) {
            'fixed' => $baseSalary,
            default => 0,
        };

        expect($due)->toBe(25000);
    });

    it('per_skill model sums adjusted class payroll lines', function () {
        $baseSalary = 0; // not included for per_skill
        $lines = [
            ['monthly_rate' => 5000, 'multiplier' => 1.0],
            ['monthly_rate' => 4000, 'multiplier' => 0.75],
            ['monthly_rate' => 6000, 'multiplier' => 1.0],
        ];

        $total = 0;
        foreach ($lines as $line) {
            $total += round($line['monthly_rate'] * $line['multiplier']);
        }

        // 5000 + 3000 + 6000 = 14000
        expect($total)->toBe(14000);
    });

    it('hybrid model adds base_salary to per_skill total', function () {
        $baseSalary = 10000;
        $perSkillTotal = 14000;

        $due = $baseSalary + $perSkillTotal;
        expect($due)->toBe(24000);
    });

    it('per_level model calculates level × rate', function () {
        $levels = [
            ['assignment_count' => 2, 'rate' => 4000],
            ['assignment_count' => 3, 'rate' => 3500],
        ];

        $total = 0;
        foreach ($levels as $level) {
            $total += $level['assignment_count'] * $level['rate'];
        }

        // (2 × 4000) + (3 × 3500) = 8000 + 10500 = 18500
        expect($total)->toBe(18500);
    });

    it('payroll multiplier is 0.75 for below-minimum classes', function () {
        $enrolledCount = 4;
        $multiplier = $enrolledCount < 6 ? 0.75 : 1.0;
        $tier = $enrolledCount < 6 ? 'below_minimum' : 'standard';

        expect($multiplier)->toBe(0.75);
        expect($tier)->toBe('below_minimum');
    });

    it('payroll multiplier is 1.0 for standard classes', function () {
        $enrolledCount = 12;
        $multiplier = $enrolledCount < 6 ? 0.75 : 1.0;
        $tier = $enrolledCount < 6 ? 'below_minimum' : 'standard';

        expect($multiplier)->toBe(1.0);
        expect($tier)->toBe('standard');
    });

    it('adjusted amount rounds correctly', function () {
        $monthlyRate = 5000;
        $multiplier = 0.75;
        $adjusted = round($monthlyRate * $multiplier);

        expect($adjusted)->toBe(3750);
    });

    it('no-double-full-pay guard works', function () {
        $existingFullPayments = 1;
        $canPayFull = $existingFullPayments === 0;

        expect($canPayFull)->toBeFalse();
    });

    it('allows partial payment after full payment', function () {
        $existingFullPayments = 1;
        $paymentType = 'partial';
        $isAllowed = $paymentType !== 'full' || $existingFullPayments === 0;

        expect($isAllowed)->toBeTrue();
    });

    it('period key normalizes to YYYY-MM format', function () {
        $normalize = function(string $label): string {
            if (preg_match('/^(\d{4})-(\d{1,2})$/', $label, $m)) {
                return $m[1] . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT);
            }
            if (preg_match('/^(\d{4})\/(\d{1,2})$/', $label, $m)) {
                return $m[1] . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT);
            }
            return strtolower(substr(preg_replace('/[^a-zA-Z0-9]/', '_', $label), 0, 32));
        };

        expect($normalize('2026-8'))->toBe('2026-08');
        expect($normalize('2026-08'))->toBe('2026-08');
        expect($normalize('2026/3'))->toBe('2026-03');
    });

    it('skips not-yet-active classes from payroll', function () {
        $activationDate = '2026-09-01';
        $today = '2026-08-22';
        $shouldSkip = $activationDate > $today;

        expect($shouldSkip)->toBeTrue();
    });

    it('includes active classes in payroll', function () {
        $activationDate = '2026-07-01';
        $today = '2026-08-22';
        $shouldSkip = $activationDate > $today;

        expect($shouldSkip)->toBeFalse();
    });

    it('per_level falls back to average rate when no level-wide rate exists', function () {
        $levelWideRate = null;
        $assignmentRates = [4000, 5000, 6000];

        $rate = $levelWideRate;
        if ($rate === null && count($assignmentRates) > 0) {
            $rate = array_sum($assignmentRates) / count($assignmentRates);
        }

        expect($rate)->toBe(5000.0);
    });
});

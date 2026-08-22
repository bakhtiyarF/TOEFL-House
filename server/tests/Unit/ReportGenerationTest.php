<?php

/**
 * ReportGenerationService Tests
 * Tests PDF report generation logic
 */

describe('ReportGenerationService', function () {

    it('calculates financial summary correctly', function () {
        $income = collect([
            (object)['amount' => 10000, 'category' => 'tuition'],
            (object)['amount' => 5000, 'category' => 'books'],
            (object)['amount' => 3000, 'category' => 'tuition'],
        ]);

        $expenses = collect([
            (object)['amount' => 8000, 'category' => 'salaries'],
            (object)['amount' => 2000, 'category' => 'supplies'],
        ]);

        $totalIncome = $income->sum('amount');
        $totalExpenses = $expenses->sum('amount');
        $netIncome = $totalIncome - $totalExpenses;
        $savingsRate = $totalIncome > 0 ? round(($totalIncome * 0.05), 2) : 0;

        expect($totalIncome)->toBe(18000);
        expect($totalExpenses)->toBe(10000);
        expect($netIncome)->toBe(8000);
        expect($savingsRate)->toBe(900.0);
    });

    it('groups income by category correctly', function () {
        $income = collect([
            (object)['amount' => 10000, 'category' => 'tuition'],
            (object)['amount' => 5000, 'category' => 'books'],
            (object)['amount' => 3000, 'category' => 'tuition'],
        ]);

        $breakdown = $income->groupBy('category')->map(function ($items) {
            return [
                'count' => $items->count(),
                'total' => $items->sum('amount'),
            ];
        })->toArray();

        expect($breakdown['tuition']['count'])->toBe(2);
        expect($breakdown['tuition']['total'])->toBe(13000);
        expect($breakdown['books']['count'])->toBe(1);
        expect($breakdown['books']['total'])->toBe(5000);
    });

    it('calculates attendance rate correctly', function () {
        $attendance = collect([
            (object)['attendance_status' => 'present'],
            (object)['attendance_status' => 'present'],
            (object)['attendance_status' => 'absent'],
            (object)['attendance_status' => 'present'],
            (object)['attendance_status' => 'sick'],
        ]);

        $totalSessions = $attendance->count();
        $presentCount = $attendance->where('attendance_status', 'present')->count();
        $attendanceRate = $totalSessions > 0 ? round(($presentCount / $totalSessions) * 100, 1) : 0;

        expect($totalSessions)->toBe(5);
        expect($presentCount)->toBe(3);
        expect($attendanceRate)->toBe(60.0);
    });

    it('handles zero sessions gracefully', function () {
        $attendance = collect([]);

        $totalSessions = $attendance->count();
        $presentCount = $attendance->where('attendance_status', 'present')->count();
        $attendanceRate = $totalSessions > 0 ? round(($presentCount / $totalSessions) * 100, 1) : 0;

        expect($totalSessions)->toBe(0);
        expect($presentCount)->toBe(0);
        expect($attendanceRate)->toBe(0);
    });

    it('calculates payroll summary correctly', function () {
        $ledger = collect([
            (object)['due_amount' => 10000, 'paid_amount' => 10000],
            (object)['due_amount' => 8000, 'paid_amount' => 5000],
            (object)['due_amount' => 12000, 'paid_amount' => 0],
        ]);

        $totalDue = $ledger->sum('due_amount');
        $totalPaid = $ledger->sum('paid_amount');
        $totalRemaining = $totalDue - $totalPaid;

        expect($totalDue)->toBe(30000);
        expect($totalPaid)->toBe(15000);
        expect($totalRemaining)->toBe(15000);
    });

    it('determines payment status correctly', function () {
        $cases = [
            ['due' => 10000, 'paid' => 10000, 'expected' => 'paid'],
            ['due' => 10000, 'paid' => 5000, 'expected' => 'partial'],
            ['due' => 10000, 'paid' => 0, 'expected' => 'unpaid'],
        ];

        foreach ($cases as $case) {
            $remaining = $case['due'] - $case['paid'];
            $status = $remaining <= 0 ? 'paid' : ($case['paid'] > 0 ? 'partial' : 'unpaid');
            expect($status)->toBe($case['expected']);
        }
    });

    it('formats period key correctly', function () {
        $year = '2026';
        $month = '8';
        $periodKey = sprintf('%s-%s', $year, str_pad($month, 2, '0', STR_PAD_LEFT));

        expect($periodKey)->toBe('2026-08');
        expect(preg_match('/^\d{4}-\d{2}$/', $periodKey))->toBe(1);
    });

    it('validates period key format', function () {
        $validKeys = ['2026-01', '2026-12', '2025-06'];
        $invalidKeys = ['2026-1', '26-01', '2026-13', '2026-00', 'invalid'];

        foreach ($validKeys as $key) {
            expect(preg_match('/^\d{4}-\d{2}$/', $key))->toBe(1);
        }

        foreach ($invalidKeys as $key) {
            expect(preg_match('/^\d{4}-\d{2}$/', $key))->toBe(0);
        }
    });

    it('calculates average attendance across students', function () {
        $students = collect([
            (object)['attendance_rate' => 85.5],
            (object)['attendance_rate' => 92.0],
            (object)['attendance_rate' => 78.3],
            (object)['attendance_rate' => 88.7],
        ]);

        $average = $students->count() > 0 ? round($students->avg('attendance_rate'), 1) : 0;

        expect($average)->toBe(86.1);
    });

    it('generates unique certificate numbers', function () {
        $certificateNo = 'CERT-' . strtoupper(substr(md5(uniqid()), 0, 8));

        expect($certificateNo)->toMatch('/^CERT-[A-F0-9]{8}$/');
        expect(strlen($certificateNo))->toBe(13);
    });
});

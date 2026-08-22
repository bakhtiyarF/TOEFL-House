<?php

/**
 * TuitionCalculationService Tests
 * Tests the discount/scholarship/payable pipeline (02 §6)
 */

use App\Modules\FinancePayroll\Services\TuitionCalculationService;
use App\Modules\PlatformServices\Services\RuleEngineService;

beforeEach(function () {
    $this->ruleEngine = new RuleEngineService();
    $this->service = new TuitionCalculationService($this->ruleEngine);
});

describe('TuitionCalculationService', function () {

    it('calculates basic tuition with no discount or scholarship', function () {
        $result = $this->service->resolveStudentFinanceAmounts(
            grossTuition: 10000,
            requestedDiscountPercent: 0,
            requestedScholarshipPercent: 0,
            amountPaid: 0,
            branchId: null,
        );

        expect($result['grossTuition'])->toBe(10000.0);
        expect($result['discountPercent'])->toBe(0.0);
        expect($result['scholarshipPercent'])->toBe(0.0);
        expect($result['discountAmount'])->toBe(0.0);
        expect($result['scholarshipAmount'])->toBe(0.0);
        expect($result['netTuition'])->toBe(10000);
        expect($result['remainingDebt'])->toBe(10000);
        expect($result['paidPercentage'])->toBe(0.0);
        expect($result['finalPayable'])->toBe(10000);
    });

    it('applies discount correctly', function () {
        $result = $this->service->resolveStudentFinanceAmounts(
            grossTuition: 10000,
            requestedDiscountPercent: 10,
            requestedScholarshipPercent: 0,
            amountPaid: 0,
            branchId: null,
        );

        expect($result['discountPercent'])->toBe(10.0);
        expect($result['discountAmount'])->toBe(1000.0);
        expect($result['netTuition'])->toBe(9000);
    });

    it('applies scholarship correctly', function () {
        $result = $this->service->resolveStudentFinanceAmounts(
            grossTuition: 10000,
            requestedDiscountPercent: 0,
            requestedScholarshipPercent: 25,
            amountPaid: 0,
            branchId: null,
        );

        expect($result['scholarshipPercent'])->toBe(25.0);
        expect($result['scholarshipAmount'])->toBe(2500.0);
        expect($result['netTuition'])->toBe(7500);
    });

    it('applies both discount and scholarship', function () {
        $result = $this->service->resolveStudentFinanceAmounts(
            grossTuition: 10000,
            requestedDiscountPercent: 10,
            requestedScholarshipPercent: 20,
            amountPaid: 0,
            branchId: null,
        );

        // 10% discount = 1000, 20% scholarship = 2000
        // Net = 10000 - 1000 - 2000 = 7000
        expect($result['netTuition'])->toBe(7000);
        expect($result['remainingDebt'])->toBe(7000);
    });

    it('calculates paid percentage correctly', function () {
        $result = $this->service->resolveStudentFinanceAmounts(
            grossTuition: 10000,
            requestedDiscountPercent: 0,
            requestedScholarshipPercent: 0,
            amountPaid: 5000,
            branchId: null,
        );

        expect($result['totalPaid'])->toBe(5000.0);
        expect($result['remainingDebt'])->toBe(5000);
        expect($result['paidPercentage'])->toBe(50.0);
    });

    it('handles fully paid tuition', function () {
        $result = $this->service->resolveStudentFinanceAmounts(
            grossTuition: 10000,
            requestedDiscountPercent: 0,
            requestedScholarshipPercent: 0,
            amountPaid: 10000,
            branchId: null,
        );

        expect($result['remainingDebt'])->toBe(0);
        expect($result['paidPercentage'])->toBe(100.0);
    });

    it('clamps percentages to 0-100 range', function () {
        $result = $this->service->resolveStudentFinanceAmounts(
            grossTuition: 10000,
            requestedDiscountPercent: 150, // should clamp to 100
            requestedScholarshipPercent: -10, // should clamp to 0
            amountPaid: 0,
            branchId: null,
        );

        expect($result['discountPercent'])->toBe(100.0);
        expect($result['scholarshipPercent'])->toBe(0.0);
    });

    it('floors net tuition at 0', function () {
        $result = $this->service->resolveStudentFinanceAmounts(
            grossTuition: 10000,
            requestedDiscountPercent: 60,
            requestedScholarshipPercent: 60, // total 120% would go negative
            amountPaid: 0,
            branchId: null,
        );

        expect($result['netTuition'])->toBeGreaterThanOrEqual(0);
        expect($result['remainingDebt'])->toBeGreaterThanOrEqual(0);
    });

    it('provides summary with convenience flags', function () {
        $summary = $this->service->summarizeStudentFinance(
            grossTuition: 10000,
            discountPercent: 10,
            scholarshipPercent: 20,
            amountPaid: 7000,
            branchId: null,
        );

        expect($summary['isFullyPaid'])->toBeTrue();
        expect($summary['hasDiscount'])->toBeTrue();
        expect($summary['hasScholarship'])->toBeTrue();
    });

    it('handles zero gross tuition', function () {
        $result = $this->service->resolveStudentFinanceAmounts(
            grossTuition: 0,
            requestedDiscountPercent: 10,
            requestedScholarshipPercent: 0,
            amountPaid: 0,
            branchId: null,
        );

        expect($result['netTuition'])->toBe(0);
        expect($result['paidPercentage'])->toBe(100.0); // special case: 0 tuition = fully paid
    });

    it('never returns negative debt', function () {
        $result = $this->service->resolveStudentFinanceAmounts(
            grossTuition: 5000,
            requestedDiscountPercent: 0,
            requestedScholarshipPercent: 0,
            amountPaid: 10000, // overpaid
            branchId: null,
        );

        expect($result['remainingDebt'])->toBe(0);
    });
});

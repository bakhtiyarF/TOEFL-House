<?php

/**
 * RuleEngineService Tests
 * Tests the core rule evaluation logic (02 §7)
 */

use App\Modules\PlatformServices\Services\RuleEngineService;

describe('RuleEngineService', function () {

    it('evaluates simple arithmetic formulas', function () {
        $service = new RuleEngineService();

        expect($service->evaluateFormula('10 + 20', []))->toBe(30.0);
        expect($service->evaluateFormula('100 - 25', []))->toBe(75.0);
        expect($service->evaluateFormula('5 * 4', []))->toBe(20.0);
        expect($service->evaluateFormula('100 / 4', []))->toBe(25.0);
    });

    it('evaluates formulas with variables from data context', function () {
        $service = new RuleEngineService();

        expect($service->evaluateFormula('amount * 0.05', ['amount' => 1000]))->toBe(50.0);
        expect($service->evaluateFormula('totalSkillRates', ['totalSkillRates' => 25000]))->toBe(25000.0);
    });

    it('treats unknown variables as 0', function () {
        $service = new RuleEngineService();

        expect($service->evaluateFormula('unknownVar + 10', []))->toBe(10.0);
        expect($service->evaluateFormula('missing * 5', []))->toBe(0.0);
    });

    it('returns 0 for malformed formulas (safety guarantee 02 §7.3)', function () {
        $service = new RuleEngineService();

        expect($service->evaluateFormula('', []))->toBe(0.0);
        expect($service->evaluateFormula('   ', []))->toBe(0.0);
        expect($service->evaluateFormula('eval("1+1")', []))->toBe(0.0);
    });

    it('handles parentheses correctly', function () {
        $service = new RuleEngineService();

        expect($service->evaluateFormula('(10 + 5) * 2', []))->toBe(30.0);
        expect($service->evaluateFormula('(100 - 20) / 4', []))->toBe(20.0);
    });

    it('handles division by zero gracefully', function () {
        $service = new RuleEngineService();

        expect($service->evaluateFormula('100 / 0', []))->toBe(0.0);
    });

    it('handles negative numbers', function () {
        $service = new RuleEngineService();

        expect($service->evaluateFormula('-5 + 10', []))->toBe(5.0);
        expect($service->evaluateFormula('10 - 20', []))->toBe(-10.0);
    });
});

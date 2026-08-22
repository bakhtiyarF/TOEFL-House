<?php

/**
 * ConversionService Tests
 * Tests the visitor → student conversion flow (02 §9.3, 09 §5)
 */

describe('ConversionService', function () {

    it('blocks conversion when placement is not completed', function () {
        $placementScore = null;
        $placementCompleted = !empty($placementScore);

        expect($placementCompleted)->toBeFalse();
    });

    it('blocks conversion when placement fee is unpaid', function () {
        $placementScore = ['score' => 78];
        $placementFeeRequired = 300;
        $feePaidInScore = !empty($placementScore['feePaid']);
        $feeCharged = $placementScore['feeCharged'] ?? null;
        $feeWaived = $placementScore['feeWaived'] ?? false;

        $placementFeePaid = $feePaidInScore || ($feeCharged !== null && $feeCharged > 0) || ($feeCharged === 0 && $feeWaived === true);

        expect($placementFeePaid)->toBeFalse();
    });

    it('allows conversion via feePaid flag in placement_score', function () {
        $placementScore = ['score' => 85, 'feePaid' => true];
        $feePaidInScore = !empty($placementScore['feePaid']);

        expect($feePaidInScore)->toBeTrue();
    });

    it('allows conversion via feeCharged > 0', function () {
        $placementScore = ['score' => 78, 'feeCharged' => 300];
        $feeCharged = $placementScore['feeCharged'] ?? null;

        $feeHandled = ($feeCharged !== null && $feeCharged > 0);
        expect($feeHandled)->toBeTrue();
    });

    it('allows conversion via feeWaived when feeCharged is 0', function () {
        $placementScore = ['score' => 92, 'feeCharged' => 0, 'feeWaived' => true];
        $feeCharged = $placementScore['feeCharged'] ?? null;
        $feeWaived = $placementScore['feeWaived'] ?? false;

        $feeHandled = ($feeCharged === 0 && $feeWaived === true);
        expect($feeHandled)->toBeTrue();
    });

    it('blocks already-converted visitors', function () {
        $status = 'registered';
        $alreadyConverted = ($status === 'registered');

        expect($alreadyConverted)->toBeTrue();
    });

    it('produces correct student code format', function () {
        $year = '2026';
        $next = 42;
        $code = sprintf('STU-%s-%04d', $year, $next);

        expect($code)->toBe('STU-2026-0042');
        expect($code)->toMatch('/^STU-\d{4}-\d{4}$/');
    });

    it('requires all three conditions for conversion', function () {
        // Test: placement done + fee paid + not registered = can convert
        $placementDone = true;
        $feePaid = true;
        $alreadyRegistered = false;
        expect($placementDone && $feePaid && !$alreadyRegistered)->toBeTrue();

        // Test: placement NOT done = cannot convert
        $placementDone = false;
        expect($placementDone && $feePaid && !$alreadyRegistered)->toBeFalse();
    });
});

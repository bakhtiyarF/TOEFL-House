<?php

namespace App\Modules\FinancePayroll\Services;

use App\Modules\PlatformServices\Services\RuleEngineService;

/**
 * Tuition Calculation Service
 *
 * The ONE authorized implementation of the discount/scholarship/payable pipeline
 * (02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md §6). No other module or controller
 * reimplements this math.
 */
class TuitionCalculationService
{
    public function __construct(
        private RuleEngineService $ruleEngine
    ) {}

    /**
     * Resolve all student finance amounts
     *
     * Exact algorithm from 02 §6 — step order preserved:
     * discount → scholarship → base formulas → finance-rule overrides
     *
     * @return array{
     *   grossTuition: float,
     *   discountPercent: float,
     *   scholarshipPercent: float,
     *   discountAmount: float,
     *   scholarshipAmount: float,
     *   netTuition: float,
     *   totalPaid: float,
     *   remainingDebt: float,
     *   paidPercentage: float,
     *   finalPayable: float
     * }
     */
    public function resolveStudentFinanceAmounts(
        float $grossTuition,
        float $requestedDiscountPercent,
        float $requestedScholarshipPercent,
        float $amountPaid,
        ?string $branchId
    ): array {
        // Step 1: Clamp inputs
        $gross = max(0, $grossTuition);
        $paid = max(0, $amountPaid);
        $discountPercent = max(0, $requestedDiscountPercent);
        $scholarshipPercent = max(0, $requestedScholarshipPercent);

        // Step 2: Run discount rules
        $discountOutputs = [];
        if ($discountPercent > 0) {
            $discountResult = $this->ruleEngine->evaluate('discount', $branchId, [
                'discountPercent' => $discountPercent,
                'grossTuition' => $gross,
                'amountPaid' => $paid,
            ]);
            if (isset($discountResult['finalOutputs']['discountPercent'])) {
                $discountPercent = (float)$discountResult['finalOutputs']['discountPercent'];
            }
            $discountOutputs = $discountResult['finalOutputs'];
        }

        // Step 3: ALWAYS run scholarship rules
        $scholarshipResult = $this->ruleEngine->evaluate('scholarship', $branchId, [
            'scholarshipPercent' => $scholarshipPercent,
            'discountPercent' => $discountPercent,
            'grossTuition' => $gross,
            'amountPaid' => $paid,
        ]);

        if (isset($scholarshipResult['finalOutputs']['scholarshipPercent'])) {
            $scholarshipPercent = (float)$scholarshipResult['finalOutputs']['scholarshipPercent'];
        } elseif ($scholarshipPercent <= 0 && isset($scholarshipResult['finalOutputs']['discountPercent'])) {
            // Some scholarship rules reuse discountPercent as award rate
            $val = (float)$scholarshipResult['finalOutputs']['discountPercent'];
            if ($val > $discountPercent) {
                $scholarshipPercent = $val - $discountPercent;
            }
        }

        // Step 4: Clamp percents to [0, 100]
        $discountPercent = max(0, min(100, $discountPercent));
        $scholarshipPercent = max(0, min(100, $scholarshipPercent));

        // Step 5: Calculate amounts
        $discountAmount = $gross * $discountPercent / 100;
        $scholarshipAmount = $gross * $scholarshipPercent / 100;
        $netTuition = max(0, $gross - $discountAmount - $scholarshipAmount);
        $remainingDebt = max(0, $netTuition - $paid);
        $paidPercentage = $netTuition <= 0 ? 100 : min(100, round($paid / $netTuition * 100));

        // Step 6: Run finance rules (may override step 5 values)
        $financeResult = $this->ruleEngine->evaluate('finance', $branchId, [
            'grossTuition' => $gross,
            'discountPercent' => $discountPercent,
            'scholarshipPercent' => $scholarshipPercent,
            'discountAmount' => $discountAmount,
            'scholarshipAmount' => $scholarshipAmount,
            'netTuition' => $netTuition,
            'remainingDebt' => $remainingDebt,
            'paidPercentage' => $paidPercentage,
            'amountPaid' => $paid,
            'transactionType' => 'tuition',
            'studentFinance' => true,
        ]);

        $outputs = $financeResult['finalOutputs'];

        // Apply overrides from finance rules
        if (isset($outputs['discountAmount'])) $discountAmount = (float)$outputs['discountAmount'];
        if (isset($outputs['scholarshipAmount'])) $scholarshipAmount = (float)$outputs['scholarshipAmount'];
        if (isset($outputs['netTuition'])) $netTuition = (float)$outputs['netTuition'];
        if (isset($outputs['remainingDebt'])) $remainingDebt = (float)$outputs['remainingDebt'];
        if (isset($outputs['paidPercentage'])) $paidPercentage = (float)$outputs['paidPercentage'];
        if (isset($outputs['discountPercent'])) $discountPercent = (float)$outputs['discountPercent'];
        if (isset($outputs['scholarshipPercent'])) $scholarshipPercent = (float)$outputs['scholarshipPercent'];

        // 'finalPayable' specifically overrides netTuition
        if (isset($outputs['finalPayable'])) {
            $netTuition = (float)$outputs['finalPayable'];
        }

        // Step 7: Round and floor at 0
        $discountAmount = round($discountAmount);
        $scholarshipAmount = round($scholarshipAmount);
        $netTuition = max(0, round($netTuition));
        $remainingDebt = max(0, round($remainingDebt));

        return [
            'grossTuition' => $gross,
            'discountPercent' => $discountPercent,
            'scholarshipPercent' => $scholarshipPercent,
            'discountAmount' => $discountAmount,
            'scholarshipAmount' => $scholarshipAmount,
            'netTuition' => $netTuition,
            'totalPaid' => $paid,
            'remainingDebt' => $remainingDebt,
            'paidPercentage' => $paidPercentage,
            'finalPayable' => $netTuition,
        ];
    }

    /**
     * Convenience: summarize a student's financial state for display
     */
    public function summarizeStudentFinance(
        float $grossTuition,
        float $discountPercent,
        float $scholarshipPercent,
        float $amountPaid,
        ?string $branchId
    ): array {
        $result = $this->resolveStudentFinanceAmounts(
            $grossTuition,
            $discountPercent,
            $scholarshipPercent,
            $amountPaid,
            $branchId
        );

        return [
            ...$result,
            'isFullyPaid' => $result['remainingDebt'] <= 0,
            'hasDiscount' => $result['discountPercent'] > 0,
            'hasScholarship' => $result['scholarshipPercent'] > 0,
        ];
    }
}

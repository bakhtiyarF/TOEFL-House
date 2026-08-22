<?php

namespace App\Modules\CrmEnrollment\Services;

use App\Modules\PlatformServices\Services\RuleEngineService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Conversion Service
 *
 * Implements the visitor → student conversion readiness check (02 §9.3)
 * and the actual conversion flow (09 §5).
 *
 * Conversion is blocked server-side — never just hidden in the UI (03 §7).
 */
class ConversionService
{
    public function __construct(
        private RuleEngineService $ruleEngine
    ) {}

    /**
     * Check if a visitor is ready to convert to a student (02 §9.3)
     *
     * @return array{canConvert: bool, reasons: string[], placementCompleted: bool, placementFeePaid: bool}
     */
    public function getConversionReadiness(string $visitorId): array
    {
        $visitor = DB::table('visitors')->where('id', $visitorId)->first();

        if (!$visitor) {
            return ['canConvert' => false, 'reasons' => ['Visitor not found.'], 'placementCompleted' => false, 'placementFeePaid' => false];
        }

        $reasons = [];

        // Already converted?
        if ($visitor->status === 'registered') {
            return ['canConvert' => false, 'reasons' => ['Already converted.'], 'placementCompleted' => true, 'placementFeePaid' => true];
        }

        // Check placement completion
        $placementScore = json_decode($visitor->placement_score ?? '{}', true);
        $placementCompleted = !empty($placementScore) && isset($placementScore['score']);

        if (!$placementCompleted) {
            $reasons[] = "Visitor stage is '{$visitor->stage}'; complete placement first.";
        }

        // Check placement fee
        $placementFeePaid = false;

        if ($placementCompleted) {
            // Resolve placement fee via rule engine (02 §7.4 #1)
            $feeResult = $this->ruleEngine->evaluate('fee', $visitor->branch_id, [
                'isFirstPlacementTest' => true,
            ]);
            $placementFeeRequired = $feeResult['finalOutputs']['placementTestFee'] ?? 300;

            if ($placementFeeRequired > 0) {
                // Three ways placement fee can be satisfied (02 §9.3):
                // 1. A financial_transactions row exists
                $feeTransaction = DB::table('financial_transactions')
                    ->where('reference_id', $visitor->id)
                    ->where('category', 'placement')
                    ->where('type', 'income')
                    ->exists();

                // 2. placement_score JSON flags feePaid=true
                $feePaidInScore = !empty($placementScore['feePaid']);

                // 3. feeCharged > 0 OR (feeCharged == 0 AND feeWaived == true)
                $feeCharged = $placementScore['feeCharged'] ?? null;
                $feeWaived = $placementScore['feeWaived'] ?? false;
                $feeHandledInScore = ($feeCharged !== null && $feeCharged > 0) || ($feeCharged === 0 && $feeWaived === true);

                $placementFeePaid = $feeTransaction || $feePaidInScore || $feeHandledInScore;

                if (!$placementFeePaid) {
                    $reasons[] = "Placement fee ({$placementFeeRequired} AFN) not recorded.";
                }
            } else {
                $placementFeePaid = true; // no fee required
            }
        } else {
            $placementFeePaid = false;
        }

        // Stage-based blocking
        $earlyStages = ['lead', 'inquiry', 'follow_up', 'placement_booking'];
        if (in_array($visitor->stage, $earlyStages) && !$placementCompleted) {
            // Already added above
        }

        $canConvert = empty($reasons) && $placementCompleted && $placementFeePaid;

        return [
            'canConvert' => $canConvert,
            'reasons' => $reasons,
            'placementCompleted' => $placementCompleted,
            'placementFeePaid' => $placementFeePaid,
        ];
    }

    /**
     * Assert visitor is ready — throws if not (HTTP 400)
     */
    public function assertVisitorReadyForConversion(string $visitorId): array
    {
        $readiness = $this->getConversionReadiness($visitorId);

        if (!$readiness['canConvert']) {
            throw new \RuntimeException(
                'Cannot convert: ' . implode(' ', $readiness['reasons']),
                400
            );
        }

        return $readiness;
    }

    /**
     * Convert a visitor to a student (09 §5)
     *
     * On successful conversion: creates students row, pinned enrollments row,
     * advances visitor stage, appends STUDENT_REGISTERED journey event.
     * All wrapped in a single transaction.
     */
    public function convert(string $visitorId, array $enrollmentData, string $actorUserId, string $actorName): array
    {
        // Gate check — throws if not ready
        $this->assertVisitorReadyForConversion($visitorId);

        return DB::transaction(function () use ($visitorId, $enrollmentData, $actorUserId, $actorName) {
            $visitor = DB::table('visitors')->where('id', $visitorId)->first();
            $studentId = Str::uuid()->toString();

            // Generate student code
            $year = now()->format('Y');
            $lastCode = DB::table('students')
                ->where('student_code', 'like', "STU-{$year}-%")
                ->orderByDesc('student_code')
                ->value('student_code');
            $next = $lastCode ? (int)substr($lastCode, -4) + 1 : 1;
            $studentCode = sprintf('STU-%s-%04d', $year, $next);

            // Create student record
            DB::table('students')->insert([
                'id' => $studentId,
                'student_code' => $studentCode,
                'full_name' => $visitor->full_name,
                'phone' => $visitor->phone,
                'email' => $visitor->email,
                'gender' => $visitor->gender,
                'father_name' => $visitor->father_name,
                'address_region' => $visitor->address_region,
                'tazkira_no' => $visitor->tazkira_no,
                'whatsapp' => $visitor->whatsapp,
                'dob' => $visitor->dob,
                'school_or_university' => $visitor->school_or_university,
                'emergency_contact_name' => $visitor->emergency_contact_name,
                'emergency_contact_phone' => $visitor->emergency_contact_phone,
                'placement_score' => $visitor->placement_score,
                'lead_id' => $visitor->id,
                'status' => 'active',
                'registration_date' => now()->toDateString(),
                'discount_percent' => 0,
                'branch_id' => $visitor->branch_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create pinned enrollment (05 §6 — copy-on-write)
            if (!empty($enrollmentData['program_version_id'])) {
                $feeRules = DB::table('fee_rules')
                    ->where(function ($q) use ($enrollmentData) {
                        $q->where('program_version_id', $enrollmentData['program_version_id'])
                          ->orWhereNull('program_version_id');
                    })
                    ->where(function ($q) use ($visitor) {
                        $q->where('branch_id', $visitor->branch_id)->orWhereNull('branch_id');
                    })
                    ->get()->toArray();

                $enrollmentId = Str::uuid()->toString();
                DB::table('enrollments')->insert([
                    'id' => $enrollmentId,
                    'student_id' => $studentId,
                    'program_id' => $enrollmentData['program_id'] ?? null,
                    'program_name' => $enrollmentData['program_name'] ?? 'General English',
                    'program_version_id' => $enrollmentData['program_version_id'],
                    'fee_snapshot_json' => json_encode([
                        'snapshot_at' => now()->toIso8601String(),
                        'program_version_id' => $enrollmentData['program_version_id'],
                        'fee_rules' => $feeRules,
                    ]),
                    'enrollment_type' => 'new',
                    'status' => 'active',
                    'started_at' => now(),
                    'branch_id' => $visitor->branch_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Journey event: ENROLLMENT_CREATED
                DB::table('student_journey_events')->insert([
                    'id' => Str::uuid()->toString(),
                    'student_id' => $studentId,
                    'event_type' => 'ENROLLMENT_CREATED',
                    'occurred_at' => now(),
                    'enrollment_id' => $enrollmentId,
                    'actor_user_id' => $actorUserId,
                    'actor_name' => $actorName,
                ]);
            }

            // Journey event: STUDENT_REGISTERED
            DB::table('student_journey_events')->insert([
                'id' => Str::uuid()->toString(),
                'student_id' => $studentId,
                'event_type' => 'STUDENT_REGISTERED',
                'occurred_at' => now(),
                'payload' => json_encode([
                    'full_name' => $visitor->full_name,
                    'converted_from_visitor' => $visitor->id,
                ]),
                'actor_user_id' => $actorUserId,
                'actor_name' => $actorName,
            ]);

            // Advance visitor stage
            DB::table('visitors')->where('id', $visitorId)->update([
                'stage' => 'enrollment',
                'status' => 'registered',
                'updated_at' => now(),
            ]);

            return [
                'student_id' => $studentId,
                'student_code' => $studentCode,
                'visitor_id' => $visitorId,
            ];
        });
    }
}

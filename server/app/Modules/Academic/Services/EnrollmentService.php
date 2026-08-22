<?php

namespace App\Modules\Academic\Services;

use App\Modules\Academic\Models\Student;
use App\Modules\Academic\Models\Enrollment;
use App\Modules\Academic\Models\ProgramVersion;
use App\Modules\Academic\Models\FeeRule;
use App\Modules\FinancePayroll\Services\TuitionCalculationService;
use App\Modules\Iam\Services\BranchScopeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Enrollment Service
 * Handles student enrollment with copy-on-write program versioning + fee snapshots.
 * Per 02_BUSINESS_LOGIC spec and 05_ACADEMIC_MODULE.
 */
class EnrollmentService
{
    public function __construct(
        private BranchScopeService $branchScope,
        private TuitionCalculationService $tuitionCalc,
        private CatalogService $catalog
    ) {}

    /**
     * Enroll student — the critical copy-on-write pin
     */
    public function enrollStudent(string $studentId, array $data, string $actorUserId): Enrollment
    {
        $student = Student::findOrFail($studentId);

        if (!$this->branchScope->canAccessBranch($actorUserId, $student->branch_id)) {
            throw new \Exception('Forbidden: branch scope violation');
        }

        return DB::transaction(function () use ($student, $data, $actorUserId) {
            // Resolve current program version (copy-on-write anchor)
            $programVersion = $this->catalog->getActiveProgramVersionForBranch($student->branch_id)
                ?? ProgramVersion::findOrFail($data['program_version_id'] ?? null);

            if (!$programVersion) {
                throw new \Exception('No active program version for branch');
            }

            // Snapshot fee rules at enrollment time
            $feeSnapshot = $this->tuitionCalc->calculateEnrollmentFees(
                $programVersion->id,
                $data['level_id'] ?? null,
                $student->branch_id,
                $data['enrollment_type'] ?? 'new'
            );

            $enrollment = Enrollment::create([
                'id' => Str::uuid()->toString(),
                'student_id' => $student->id,
                'program_id' => $data['program_id'],
                'program_name' => $data['program_name'] ?? $programVersion->program->name,
                'semester_name' => $data['semester_name'] ?? now()->format('Y-m'),
                'level_code' => $data['level_code'] ?? null,
                'class_id' => $data['class_id'] ?? null,
                'enrollment_type' => $data['enrollment_type'] ?? 'new',
                'status' => 'active',
                'program_version_id' => $programVersion->id,
                'fee_snapshot_json' => $feeSnapshot,
                'started_at' => now(),
                'skills_focus' => $data['skills_focus'] ?? null,
            ]);

            // Create student semester record
            $student->semesters()->create([
                'id' => Str::uuid()->toString(),
                'semester_name' => $enrollment->semester_name,
                'class_id' => $enrollment->class_id,
                'enroll_date' => now()->toDateString(),
                'fee_amount' => $feeSnapshot['net_tuition'] ?? 0,
                'status' => 'active',
            ]);

            // Journey event
            $student->addJourneyEvent('enrolled', [
                'enrollment_id' => $enrollment->id,
                'program_version_id' => $programVersion->id,
                'fee_snapshot' => $feeSnapshot,
            ], $actorUserId);

            return $enrollment->load('student', 'programVersion');
        });
    }

    public function getActiveEnrollmentsForStudent(string $studentId): \Illuminate\Support\Collection
    {
        return Enrollment::where('student_id', $studentId)
            ->where('status', 'active')
            ->with('programVersion')
            ->get();
    }

    /**
     * Update enrollment status — does NOT alter frozen snapshot
     */
    public function updateEnrollmentStatus(string $enrollmentId, string $status, string $actorUserId): Enrollment
    {
        $enrollment = Enrollment::findOrFail($enrollmentId);

        if (!$this->branchScope->canAccessBranch($actorUserId, $enrollment->student->branch_id)) {
            throw new \Exception('Forbidden');
        }

        $enrollment->update(['status' => $status]);

        if (in_array($status, ['completed', 'graduated'])) {
            $enrollment->student->addJourneyEvent('enrollment_' . $status, [
                'enrollment_id' => $enrollment->id,
            ], $actorUserId);
        }

        return $enrollment;
    }

    public function getFeeSnapshot(string $enrollmentId): array
    {
        $enrollment = Enrollment::findOrFail($enrollmentId);
        return $enrollment->fee_snapshot_json ?? [];
    }
}

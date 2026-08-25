<?php

namespace App\Modules\Academic\Http\Controllers;

use App\Modules\Academic\Models\Student;
use App\Modules\Academic\Services\PromotionService;
use App\Modules\Iam\Services\BranchScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PromotionController extends Controller
{
    public function __construct(
        private BranchScopeService $branchScopeService,
        private PromotionService $promotionService
    ) {}

    /**
     * Get promotion recommendation for a student (uses live exam avg + attendance)
     */
    public function recommend(Request $request, string $studentId): JsonResponse
    {
        $student = Student::with(['enrollments', 'examResults'])->findOrFail($studentId);

        if (!$this->branchScopeService->canAccessBranch($request->user(), $student->branch_id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $programVersionId = $request->query('program_version_id') 
            ?? $student->enrollments()->where('status', 'active')->value('program_version_id');

        if (!$programVersionId) {
            return response()->json(['message' => 'No active enrollment with program version'], 422);
        }

        $result = $this->promotionService->evaluatePromotion(
            $student, 
            $programVersionId, 
            $student->branch_id
        );

        return response()->json($result);
    }

    /**
     * Simple list of promotion rules for a version (for UI)
     */
    public function rules(string $programVersionId): JsonResponse
    {
        $rules = \App\Modules\Academic\Models\PromotionRule::where('program_version_id', $programVersionId)
            ->get();

        return response()->json($rules);
    }

    /**
     * Apply promotion decision (creates journey event + updates enrollment)
     */
    public function promote(Request $request, string $studentId): JsonResponse
    {
        $student = Student::findOrFail($studentId);

        if (!$this->branchScopeService->canAccessBranch($request->user(), $student->branch_id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'to_level_id' => 'required|uuid',
            'from_level_id' => 'nullable|uuid',
            'program_version_id' => 'nullable|uuid',
            'reason' => 'nullable|string',
        ]);

        // Find active enrollment
        $enrollment = $student->enrollments()->where('status', 'active')->first();

        if (!$enrollment) {
            return response()->json(['message' => 'No active enrollment'], 422);
        }

        // Update enrollment level (live promotion)
        $enrollment->update([
            'level_id' => $validated['to_level_id'],
            'level_code' => $validated['to_level_id'], // or resolve code
        ]);

        // Append journey event
        \App\Modules\Academic\Models\StudentJourneyEvent::create([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'student_id' => $student->id,
            'event_type' => 'PROMOTION_DECIDED',
            'occurred_at' => now(),
            'enrollment_id' => $enrollment->id,
            'payload' => [
                'from' => $validated['from_level_id'],
                'to' => $validated['to_level_id'],
                'reason' => $validated['reason'] ?? 'Manual promotion via UI',
            ],
            'actor_user_id' => $request->user()->id,
            'actor_name' => $request->user()->full_name,
        ]);

        return response()->json([
            'message' => 'Promotion applied',
            'enrollment' => $enrollment->fresh(),
        ], 200);
    }
}

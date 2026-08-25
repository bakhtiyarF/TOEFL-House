<?php

namespace App\Modules\Academic\Services;

use App\Modules\Iam\Services\BranchScopeService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * AssessmentService
 * Business logic for Homework + Exams (05_ACADEMIC)
 * Future: auto reminders, late penalties, grade calculations, promotion integration.
 */
class AssessmentService
{
    public function __construct(
        private BranchScopeService $branchScope
    ) {}

    public function listHomeworkForClass(string $classId, ?string $branchId = null): Collection
    {
        $scope = $this->branchScope->resolve(auth()->user(), $branchId ?? 'all');

        $query = DB::table('homework')
            ->join('sessions', 'homework.session_id', '=', 'sessions.id')
            ->where('sessions.class_id', $classId)
            ->select('homework.*', 'sessions.date as session_date');

        if (!$scope['isAll']) {
            $query->where('sessions.branch_id', $scope['branchId']); // safety
        }

        return $query->orderByDesc('due_date')->get();
    }

    public function getExamSummary(string $examId): array
    {
        $exam = DB::table('exams')->where('id', $examId)->first();
        if (!$exam) return [];

        $results = DB::table('exam_results')
            ->where('exam_id', $examId)
            ->get();

        $avg = $results->avg('score') ?? 0;
        $passCount = $results->where('score', '>=', 60)->count();

        return [
            'exam' => $exam,
            'total_results' => $results->count(),
            'average_score' => round($avg, 1),
            'pass_rate' => $results->count() ? round(($passCount / $results->count()) * 100) : 0,
        ];
    }

    public function createHomeworkWithRoster(string $classId, array $data, string $actorId): string
    {
        // Could auto-assign to rostered students later
        $id = (string) \Illuminate\Support\Str::uuid();
        DB::table('homework')->insert([
            'id' => $id,
            ...$data,
            'assigned_by' => $actorId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }
}

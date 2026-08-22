<?php

namespace App\Modules\PeopleHr\Services;

use App\Modules\PeopleHr\Models\Teacher;
use App\Modules\Iam\Services\BranchScopeService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Teacher Service
 * Core business logic for teachers, evaluations, transfers.
 */
class TeacherService
{
    public function __construct(
        private BranchScopeService $branchScope
    ) {}

    public function listTeachers(?string $branchId = null, array $filters = []): Collection
    {
        $scope = $this->branchScope->resolve(auth()->user(), $branchId ?? 'all');

        $query = Teacher::query()
            ->with(['branch', 'activeClasses'])
            ->when($filters['status'] ?? null, fn($q, $s) => $q->where('status', $s))
            ->when($filters['search'] ?? null, fn($q, $s) => $q->where('full_name', 'like', "%{$s}%"));

        if (!$scope['isAll']) {
            $query->where('branch_id', $scope['branchId']);
        }

        return $query->get()->map(function ($t) {
            return [
                ...$t->toArray(),
                'classes' => $t->activeClasses->count(),
                'students' => $t->total_students,
                'current_due' => $t->current_period_due_amount,
            ];
        });
    }

    public function createTeacher(array $data): Teacher
    {
        if (!$this->branchScope->canAccessBranch(auth()->id(), $data['branch_id'])) {
            throw new \Exception('Forbidden');
        }

        $teacher = Teacher::create([
            'id' => Str::uuid()->toString(),
            ...$data,
            'status' => 'active',
        ]);

        event(new \App\Events\EmployeeOnboarded($teacher));

        return $teacher;
    }

    public function updateTeacher(string $id, array $data): Teacher
    {
        $teacher = Teacher::findOrFail($id);

        if (!$this->branchScope->canAccessBranch(auth()->id(), $teacher->branch_id)) {
            throw new \Exception('Forbidden');
        }

        $teacher->update($data);
        return $teacher;
    }

    public function transferTeacher(string $id, string $newBranchId, string $reason): bool
    {
        $teacher = Teacher::findOrFail($id);

        if (!$this->branchScope->canAccessBranch(auth()->id(), $teacher->branch_id)) {
            throw new \Exception('Forbidden');
        }

        return DB::transaction(function () use ($teacher, $newBranchId, $reason) {
            $oldBranch = $teacher->branch_id;

            $teacher->update(['branch_id' => $newBranchId]);

            // Log transfer
            DB::table('teacher_transfers')->insert([
                'id' => Str::uuid()->toString(),
                'teacher_id' => $teacher->id,
                'from_branch_id' => $oldBranch,
                'to_branch_id' => $newBranchId,
                'reason' => $reason,
                'transferred_by' => auth()->id(),
                'transferred_at' => now(),
            ]);

            return true;
        });
    }

    public function recordEvaluation(string $teacherId, array $data): void
    {
        $teacher = Teacher::findOrFail($teacherId);

        DB::table('teacher_evaluations')->insert([
            'id' => Str::uuid()->toString(),
            'teacher_id' => $teacherId,
            'date' => $data['date'] ?? now()->toDateString(),
            'score' => $data['score'],
            'notes' => $data['notes'] ?? null,
            'evaluator_id' => auth()->id(),
            'created_at' => now(),
        ]);

        // Update aggregate
        $avg = DB::table('teacher_evaluations')
            ->where('teacher_id', $teacherId)
            ->avg('score');

        $teacher->update(['performance_score' => round($avg, 2)]);
    }
}

<?php

namespace App\Modules\Academic\Services;

use App\Modules\Academic\Models\AcademicClass;
use App\Modules\Academic\Models\Session;
use App\Modules\Iam\Services\BranchScopeService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Class Service
 * Business logic for classes, sessions, capacity warnings, merges.
 */
class ClassService
{
    public function __construct(
        private BranchScopeService $branchScope
    ) {}

    public function listClasses(?string $branchId = null, array $filters = []): Collection
    {
        $scope = $this->branchScope->resolve(auth()->user(), $branchId ?? 'all');

        $query = AcademicClass::query()
            ->with(['program', 'level', 'teacher'])
            ->when($filters['status'] ?? null, fn($q, $s) => $q->where('status', $s))
            ->when($filters['search'] ?? null, fn($q, $s) => $q->where('name', 'like', "%{$s}%"));

        if (!$scope['isAll']) {
            $query->where('branch_id', $scope['branchId']);
        }

        return $query->get()->map(function ($class) {
            return [
                ...$class->toArray(),
                'enrolled_count' => $class->enrolled_count,
                'fill_percent' => $class->fill_percent,
                'is_below_minimum' => $class->isBelowMinimumSize(),
            ];
        });
    }

    public function createClass(array $data, string $actorUserId): AcademicClass
    {
        if (!$this->branchScope->canAccessBranch($actorUserId, $data['branch_id'])) {
            throw new \Exception('Forbidden');
        }

        $class = AcademicClass::create([
            'id' => Str::uuid()->toString(),
            ...$data,
            'status' => 'active',
        ]);

        // Fire event for class created
        event(new \App\Events\ClassCreated($class));

        return $class;
    }

    public function updateClass(string $id, array $data, string $actorUserId): AcademicClass
    {
        $class = AcademicClass::findOrFail($id);

        if (!$this->branchScope->canAccessBranch($actorUserId, $class->branch_id)) {
            throw new \Exception('Forbidden');
        }

        $class->update($data);
        return $class;
    }

    public function mergeClass(string $sourceId, string $targetId, string $actorUserId): bool
    {
        $source = AcademicClass::findOrFail($sourceId);
        $target = AcademicClass::findOrFail($targetId);

        if ($source->branch_id !== $target->branch_id) {
            throw new \Exception('Cannot merge across branches');
        }

        return DB::transaction(function () use ($source, $target, $actorUserId) {
            // Move active semesters
            DB::table('student_semesters')
                ->where('class_id', $source->id)
                ->where('status', 'active')
                ->update(['class_id' => $target->id]);

            $source->update([
                'status' => 'cancelled',
                'merged_into_id' => $target->id,
            ]);

            // Record journey events for affected students
            $affected = DB::table('student_semesters')
                ->where('class_id', $target->id)
                ->pluck('student_id');

            foreach ($affected as $sid) {
                \App\Modules\Academic\Models\Student::find($sid)?->addJourneyEvent('class_merged', [
                    'from_class' => $source->id,
                    'to_class' => $target->id,
                ], $actorUserId);
            }

            return true;
        });
    }

    public function createSession(string $classId, array $data): Session
    {
        $class = AcademicClass::findOrFail($classId);
        return Session::create([
            'id' => Str::uuid()->toString(),
            'class_id' => $classId,
            'date' => $data['date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'topic' => $data['topic'] ?? null,
            'status' => 'scheduled',
            'teacher_id' => $data['teacher_id'] ?? $class->teacher_id,
        ]);
    }

    public function recordAttendance(string $sessionId, array $attendanceData, string $actorUserId): void
    {
        $session = Session::findOrFail($sessionId);

        DB::transaction(function () use ($session, $attendanceData, $actorUserId) {
            foreach ($attendanceData as $studentId => $status) {
                DB::table('rosters')->updateOrInsert(
                    ['session_id' => $session->id, 'student_id' => $studentId],
                    [
                        'attendance_status' => $status,
                        'marked_at' => now(),
                        'marked_by' => $actorUserId,
                    ]
                );
            }
        });

        event(new \App\Events\AttendanceRecorded($session, $attendanceData));
    }
}

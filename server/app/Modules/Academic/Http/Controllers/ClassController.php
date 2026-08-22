<?php

namespace App\Modules\Academic\Http\Controllers;

use App\Modules\Iam\Services\BranchScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClassController extends Controller
{
    public function __construct(
        private BranchScopeService $branchScopeService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $scope = $this->branchScopeService->resolve($request->user(), $request->query('branch_id', 'all'));

        $query = DB::table('classes')
            ->when($request->query('status'), fn($q, $s) => $q->where('status', $s))
            ->when($request->query('search'), fn($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderBy('name');

        if (!$scope['isAll']) {
            $query->where('branch_id', $scope['branchId']);
        }

        $classes = $query->get()->map(function ($class) {
            $enrolled = DB::table('student_semesters')
                ->where('class_id', $class->id)
                ->where('status', 'active')
                ->count();
            return [
                ...((array)$class),
                'enrolled_count' => $enrolled,
                'fill_percent' => $class->capacity > 0 ? round(($enrolled / $class->capacity) * 100) : 0,
            ];
        });

        return response()->json($classes);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $class = DB::table('classes')->where('id', $id)->first();
        if (!$class) return response()->json(['message' => 'Not found'], 404);

        if (!$this->branchScopeService->canAccessBranch($request->user(), $class->branch_id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $sessions = DB::table('sessions')
            ->where('class_id', $id)
            ->orderByDesc('date')
            ->get()
            ->map(function ($session) {
                $attendance = DB::table('rosters')
                    ->where('session_id', $session->id)
                    ->selectRaw("
                        COUNT(*) as total,
                        SUM(CASE WHEN attendance_status = 'present' THEN 1 ELSE 0 END) as present,
                        SUM(CASE WHEN attendance_status = 'absent' THEN 1 ELSE 0 END) as absent
                    ")
                    ->first();

                return [
                    ...((array)$session),
                    'attendance' => $attendance,
                ];
            });

        $roster = DB::table('student_semesters as ss')
            ->join('students as s', 'ss.student_id', '=', 's.id')
            ->where('ss.class_id', $id)
            ->where('ss.status', 'active')
            ->select('s.id', 's.student_code', 's.full_name', 's.gender', 'ss.enroll_date', 'ss.fee_amount')
            ->get();

        return response()->json([
            'class' => $class,
            'sessions' => $sessions,
            'roster' => $roster,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'teacher_id' => 'nullable|uuid',
            'program_id' => 'nullable|uuid',
            'level_id' => 'nullable|uuid',
            'capacity' => 'integer|min:1',
            'min_viable_size' => 'integer|min:1',
            'schedule_time' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'fee' => 'numeric|min:0',
            'gender_policy' => 'in:female,male,mixed',
            'branch_id' => 'required|uuid|exists:branches,id',
        ]);

        $id = Str::uuid()->toString();
        DB::table('classes')->insert([
            'id' => $id,
            ...$validated,
            'status' => 'active',
            'capacity' => $validated['capacity'] ?? 20,
            'min_viable_size' => $validated['min_viable_size'] ?? 5,
            'fee' => $validated['fee'] ?? 0,
            'gender_policy' => $validated['gender_policy'] ?? 'mixed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(DB::table('classes')->where('id', $id)->first(), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $class = DB::table('classes')->where('id', $id)->first();
        if (!$class) return response()->json(['message' => 'Not found'], 404);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'teacher_id' => 'nullable|uuid',
            'capacity' => 'integer|min:1',
            'schedule_time' => 'nullable|string',
            'status' => 'in:active,completed,cancelled',
            'fee' => 'numeric|min:0',
        ]);

        DB::table('classes')->where('id', $id)->update([...$validated, 'updated_at' => now()]);
        return response()->json(DB::table('classes')->where('id', $id)->first());
    }
}

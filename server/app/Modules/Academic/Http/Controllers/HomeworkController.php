<?php

namespace App\Modules\Academic\Http\Controllers;

use App\Modules\Iam\Services\BranchScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HomeworkController extends Controller
{
    public function __construct(
        private BranchScopeService $branchScopeService
    ) {}

    public function index(Request $request, string $classId): JsonResponse
    {
        $scope = $this->branchScopeService->resolve($request->user(), $request->query('branch_id', 'all'));

        $query = DB::table('homework')
            ->join('sessions', 'homework.session_id', '=', 'sessions.id')
            ->where('sessions.class_id', $classId)
            ->select('homework.*', 'sessions.date as session_date', 'sessions.topic')
            ->orderByDesc('homework.due_date');

        if (!$scope['isAll']) {
            $class = DB::table('classes')->where('id', $classId)->first();
            if ($class && !$this->branchScopeService->canAccessBranch($request->user(), $class->branch_id)) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
        }

        return response()->json($query->get());
    }

    public function store(Request $request, string $classId): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|uuid|exists:sessions,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);

        $session = DB::table('sessions')->where('id', $validated['session_id'])->where('class_id', $classId)->first();
        if (!$session) {
            return response()->json(['message' => 'Session not found for this class'], 404);
        }

        $class = DB::table('classes')->where('id', $classId)->first();
        if (!$class || !$this->branchScopeService->canAccessBranch($request->user(), $class->branch_id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $id = Str::uuid()->toString();
        DB::table('homework')->insert([
            'id' => $id,
            'session_id' => $validated['session_id'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'assigned_by' => $request->user()->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(DB::table('homework')->where('id', $id)->first(), 201);
    }

    public function update(Request $request, string $homeworkId): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'status' => 'nullable|in:assigned,submitted,graded,completed',
        ]);

        $homework = DB::table('homework')->where('id', $homeworkId)->first();
        if (!$homework) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $session = DB::table('sessions')->where('id', $homework->session_id)->first();
        $class = $session ? DB::table('classes')->where('id', $session->class_id)->first() : null;

        if ($class && !$this->branchScopeService->canAccessBranch($request->user(), $class->branch_id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        DB::table('homework')->where('id', $homeworkId)->update(array_merge($validated, ['updated_at' => now()]));

        return response()->json(DB::table('homework')->where('id', $homeworkId)->first());
    }

    /** Simple "mark done" helper for live UI */
    public function markDone(string $homeworkId): JsonResponse
    {
        $homework = DB::table('homework')->where('id', $homeworkId)->first();
        if (!$homework) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $session = DB::table('sessions')->where('id', $homework->session_id)->first();
        $class = $session ? DB::table('classes')->where('id', $session->class_id)->first() : null;

        if ($class && !$this->branchScopeService->canAccessBranch(auth()->user(), $class->branch_id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        DB::table('homework')->where('id', $homeworkId)->update([
            'status' => 'completed',
            'updated_at' => now(),
        ]);

        return response()->json(DB::table('homework')->where('id', $homeworkId)->first());
    }
}

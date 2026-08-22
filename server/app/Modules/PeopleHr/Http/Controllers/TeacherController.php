<?php

namespace App\Modules\PeopleHr\Http\Controllers;

use App\Modules\PeopleHr\Models\Teacher;
use App\Modules\PeopleHr\Services\TeacherService;
use App\Modules\Iam\Services\BranchScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TeacherController extends Controller
{
    public function __construct(
        private TeacherService $teacherService,
        private BranchScopeService $branchScopeService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $teachers = $this->teacherService->listTeachers(
            $request->query('branch_id'),
            $request->only(['status', 'search'])
        );

        return response()->json($teachers);
    }

    public function show(string $id): JsonResponse
    {
        $teacher = Teacher::with(['branch', 'activeClasses', 'evaluations'])->findOrFail($id);

        if (!$this->branchScopeService->canAccessBranch(request()->user(), $teacher->branch_id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($teacher);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'base_salary' => 'numeric|min:0',
            'salary_type' => 'in:fixed,per_skill,per_session,hybrid,per_level',
            'specialization' => 'nullable|string',
            'qualification' => 'nullable|string',
            'contract_type' => 'nullable|string',
            'joined_date' => 'required|date',
            'branch_id' => 'required|uuid|exists:branches,id',
        ]);

        $teacher = $this->teacherService->createTeacher($validated);
        return response()->json($teacher, 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $teacher = Teacher::findOrFail($id);

        if (!$this->branchScopeService->canAccessBranch($request->user(), $teacher->branch_id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'full_name' => 'string|max:255',
            'base_salary' => 'numeric|min:0',
            'salary_type' => 'in:fixed,per_skill,per_session,hybrid,per_level',
            'status' => 'in:active,on_leave,inactive',
            'performance_score' => 'numeric|min:0|max:5',
        ]);

        $teacher = $this->teacherService->updateTeacher($id, $validated);
        return response()->json($teacher);
    }

    public function destroy(string $id): JsonResponse
    {
        $teacher = Teacher::findOrFail($id);

        if (!$this->branchScopeService->canAccessBranch(request()->user(), $teacher->branch_id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $teacher->delete();
        return response()->json(null, 204);
    }

    public function transfer(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'new_branch_id' => 'required|uuid|exists:branches,id',
            'reason' => 'required|string|max:500',
        ]);

        $success = $this->teacherService->transferTeacher($id, $validated['new_branch_id'], $validated['reason']);

        return response()->json(['success' => $success]);
    }
}

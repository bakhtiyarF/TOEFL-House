<?php

namespace App\Modules\PeopleHr\Http\Controllers;

use App\Modules\Iam\Services\BranchScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class TeacherController extends Controller
{
    public function __construct(
        private BranchScopeService $branchScopeService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $scope = $this->branchScopeService->resolve($request->user(), $request->query('branch_id', 'all'));

        $query = DB::table('teachers')
            ->when($request->query('search'), function ($q, $s) {
                $q->where('full_name', 'like', "%{$s}%");
            })
            ->when($request->query('status'), fn($q, $s) => $q->where('status', $s))
            ->orderBy('full_name');

        if (!$scope['isAll']) {
            $query->where('branch_id', $scope['branchId']);
        }

        return response()->json($query->get());
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $teacher = DB::table('teachers')->where('id', $id)->first();
        if (!$teacher) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if (!$this->branchScopeService->canAccessBranch($request->user(), $teacher->branch_id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($teacher);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'base_salary' => 'nullable|numeric|min:0',
            'salary_type' => 'required|in:fixed,per_skill,per_session,hybrid,per_level',
            'status' => 'in:active,inactive,on_leave',
            'branch_id' => 'required|uuid|exists:branches,id',
            'joined_date' => 'required|date',
            'specialization' => 'nullable|string|max:255',
            'qualification' => 'nullable|string|max:255',
            'contract_type' => 'nullable|in:monthly,hourly,per_session',
            'user_id' => 'nullable|uuid|exists:users,id',
        ]);

        if (!$this->branchScopeService->canAccessBranch($request->user(), $validated['branch_id'])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $id = \Illuminate\Support\Str::uuid()->toString();
        DB::table('teachers')->insert([
            'id' => $id,
            ...$validated,
            'base_salary' => $validated['base_salary'] ?? 0,
            'performance_score' => 0,
            'status' => $validated['status'] ?? 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(DB::table('teachers')->where('id', $id)->first(), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $teacher = DB::table('teachers')->where('id', $id)->first();
        if (!$teacher) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if (!$this->branchScopeService->canAccessBranch($request->user(), $teacher->branch_id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'full_name' => 'string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'base_salary' => 'nullable|numeric|min:0',
            'salary_type' => 'in:fixed,per_skill,per_session,hybrid,per_level',
            'status' => 'in:active,inactive,on_leave',
            'specialization' => 'nullable|string|max:255',
        ]);

        DB::table('teachers')->where('id', $id)->update([
            ...$validated,
            'updated_at' => now(),
        ]);

        return response()->json(DB::table('teachers')->where('id', $id)->first());
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $teacher = DB::table('teachers')->where('id', $id)->first();
        if (!$teacher) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if (!$this->branchScopeService->canAccessBranch($request->user(), $teacher->branch_id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        DB::table('teachers')->where('id', $id)->delete();
        return response()->json(null, 204);
    }

    /**
     * Branch transfer (06 §6)
     */
    public function transfer(Request $request, string $id): JsonResponse
    {
        $teacher = DB::table('teachers')->where('id', $id)->first();
        if (!$teacher) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $validated = $request->validate([
            'branch_id' => 'required|uuid|exists:branches,id',
        ]);

        DB::table('teachers')->where('id', $id)->update([
            'branch_id' => $validated['branch_id'],
            'updated_at' => now(),
        ]);

        return response()->json(DB::table('teachers')->where('id', $id)->first());
    }
}

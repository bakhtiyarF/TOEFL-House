<?php

namespace App\Modules\PeopleHr\Http\Controllers;

use App\Modules\PeopleHr\Models\Employee;
use App\Modules\Iam\Services\BranchScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    public function __construct(
        private BranchScopeService $branchScopeService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $scope = $this->branchScopeService->resolve($request->user(), $request->query('branch_id', 'all'));

        $query = Employee::query()
            ->when($request->query('status'), fn($q, $s) => $q->where('status', $s))
            ->when($request->query('search'), fn($q, $s) => $q->where('full_name', 'like', "%{$s}%"))
            ->orderBy('full_name');

        if (!$scope['isAll']) {
            $query->where('branch_id', $scope['branchId']);
        }

        return response()->json($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'role' => 'nullable|string|max:100',
            'base_salary' => 'nullable|numeric|min:0',
            'status' => 'in:active,inactive',
            'joined_date' => 'required|date',
            'branch_id' => 'required|uuid|exists:branches,id',
        ]);

        if (!$this->branchScopeService->canAccessBranch($request->user(), $validated['branch_id'])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $employee = Employee::create([
            'id' => Str::uuid()->toString(),
            ...$validated,
            'status' => $validated['status'] ?? 'active',
            'base_salary' => $validated['base_salary'] ?? 0,
        ]);

        return response()->json($employee, 201);
    }

    public function show(string $id): JsonResponse
    {
        $employee = Employee::findOrFail($id);

        if (!$this->branchScopeService->canAccessBranch(request()->user(), $employee->branch_id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($employee);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $employee = Employee::findOrFail($id);

        if (!$this->branchScopeService->canAccessBranch($request->user(), $employee->branch_id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'full_name' => 'string|max:255',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'role' => 'nullable|string',
            'base_salary' => 'nullable|numeric|min:0',
            'status' => 'in:active,inactive',
        ]);

        $employee->update($validated);
        return response()->json($employee);
    }

    public function destroy(string $id): JsonResponse
    {
        $employee = Employee::findOrFail($id);

        if (!$this->branchScopeService->canAccessBranch(request()->user(), $employee->branch_id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $employee->delete();
        return response()->json(null, 204);
    }

    public function transfer(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'new_branch_id' => 'required|uuid|exists:branches,id',
            'reason' => 'required|string|max:500',
        ]);

        $employee = Employee::findOrFail($id);

        if (!$this->branchScopeService->canAccessBranch($request->user(), $employee->branch_id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $employee->update(['branch_id' => $validated['new_branch_id']]);

        // Log or event if needed (kept simple)
        return response()->json(['success' => true, 'new_branch_id' => $validated['new_branch_id']]);
    }
}

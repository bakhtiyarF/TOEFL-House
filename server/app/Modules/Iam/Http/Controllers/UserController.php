<?php

namespace App\Modules\Iam\Http\Controllers;

use App\Modules\Iam\Models\User;
use App\Modules\Iam\Services\BranchScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct(
        private BranchScopeService $branchScopeService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $scope = $this->branchScopeService->resolve(
            $request->user(),
            $request->query('branch_id', 'all')
        );

        $query = User::with('branch');

        if (!$scope['isAll']) {
            $query->where('branch_id', $scope['branchId']);
        }

        return response()->json($query->get());
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $user = User::with('branch')->findOrFail($id);

        if (!$this->branchScopeService->canAccessBranch($request->user(), $user->branch_id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($user);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => 'required|string|max:50|unique:users,username',
            'password' => 'required|string|min:8',
            'full_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'role' => 'required|in:owner,manager,finance,registrar,teacher,head_of_department,counselor,donor_manager',
            'branch_id' => 'required|uuid|exists:branches,id',
            'gender' => 'nullable|in:male,female,other',
            'department' => 'nullable|string|max:100',
            'employee_id' => 'nullable|string|max:50',
            'national_id' => 'nullable|string|max:50',
            'date_of_birth' => 'nullable|date',
            'joining_date' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        // Verify the requesting user can create users in the target branch
        if (!$this->branchScopeService->canAccessBranch($request->user(), $validated['branch_id'])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated['password'] = Hash::make($validated['password']);
        $user = User::create($validated);

        return response()->json($user->load('branch'), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if (!$this->branchScopeService->canAccessBranch($request->user(), $user->branch_id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'full_name' => 'string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'role' => 'in:owner,manager,finance,registrar,teacher,head_of_department,counselor,donor_manager',
            'branch_id' => 'uuid|exists:branches,id',
            'gender' => 'nullable|in:male,female,other',
            'department' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);
        return response()->json($user->load('branch'));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if (!$this->branchScopeService->canAccessBranch($request->user(), $user->branch_id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $user->delete();
        return response()->json(null, 204);
    }
}

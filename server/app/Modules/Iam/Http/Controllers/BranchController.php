<?php

namespace App\Modules\Iam\Http\Controllers;

use App\Modules\Iam\Models\Branch;
use App\Modules\Iam\Services\BranchScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class BranchController extends Controller
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

        $query = Branch::query();

        if (!$scope['isAll']) {
            $query->where('id', $scope['branchId']);
        }

        return response()->json($query->get());
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (!$this->branchScopeService->canAccessBranch($user, $id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json(Branch::findOrFail($id));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campus_id' => 'nullable|uuid|exists:campuses,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:branches,code',
            'location' => 'required|string|max:500',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $branch = Branch::create($validated);
        return response()->json($branch, 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (!$this->branchScopeService->canAccessBranch($user, $id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $branch = Branch::findOrFail($id);

        $validated = $request->validate([
            'campus_id' => 'nullable|uuid|exists:campuses,id',
            'name' => 'string|max:255',
            'code' => 'nullable|string|max:50|unique:branches,code,' . $id,
            'location' => 'string|max:500',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $branch->update($validated);
        return response()->json($branch);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (!$this->branchScopeService->canAccessBranch($user, $id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        Branch::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}

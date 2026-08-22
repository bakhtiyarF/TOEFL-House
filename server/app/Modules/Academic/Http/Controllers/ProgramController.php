<?php

namespace App\Modules\Academic\Http\Controllers;

use App\Modules\Academic\Models\Program;
use App\Modules\Academic\Services\CatalogService;
use App\Modules\Iam\Services\BranchScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ProgramController extends Controller
{
    public function __construct(
        private BranchScopeService $branchScopeService,
        private CatalogService $catalog
    ) {}

    public function index(Request $request): JsonResponse
    {
        $programs = $this->catalog->getPrograms($request->query('branch_id'));
        return response()->json($programs);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:programs,code',
            'description' => 'nullable|string',
            'duration_months' => 'integer|min:1',
            'branch_id' => 'nullable|uuid',
        ]);

        $program = Program::create($validated);
        return response()->json($program, 201);
    }

    public function versions(Request $request, string $programId): JsonResponse
    {
        $versions = $this->catalog->getProgramVersions($programId, $request->query('branch_id'));
        return response()->json($versions);
    }
}

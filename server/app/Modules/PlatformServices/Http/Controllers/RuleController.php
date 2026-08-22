<?php

namespace App\Modules\PlatformServices\Http\Controllers;

use App\Modules\PlatformServices\Services\RuleEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RuleController extends Controller
{
    public function __construct(
        private RuleEngineService $ruleEngine
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = DB::table('rule_definitions')
            ->when($request->query('category'), fn($q, $c) => $q->where('category', $c))
            ->when($request->query('active_only'), fn($q) => $q->where('is_active', true))
            ->orderByDesc('priority')
            ->orderByAsc('name');

        return response()->json($query->get());
    }

    public function show(string $id): JsonResponse
    {
        $rule = DB::table('rule_definitions')->where('id', $id)->first();
        if (!$rule) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $versions = DB::table('rule_versions')
            ->where('rule_id', $id)
            ->orderByDesc('version')
            ->get();

        return response()->json([
            'rule' => $rule,
            'versions' => $versions,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|in:fee,discount,promotion,attendance,payroll,scholarship,workflow,notification,finance,academic',
            'conditions' => 'required|array',
            'actions' => 'required|array',
            'priority' => 'integer|min:0',
            'is_active' => 'boolean',
            'scope_branch_id' => 'nullable|uuid|exists:branches,id',
        ]);

        $id = Str::uuid()->toString();
        DB::table('rule_definitions')->insert([
            'id' => $id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'],
            'conditions' => json_encode($validated['conditions']),
            'actions' => json_encode($validated['actions']),
            'priority' => $validated['priority'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
            'scope_branch_id' => $validated['scope_branch_id'] ?? null,
            'version' => 1,
            'last_modified_by' => $request->user()->id,
            'last_modified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(DB::table('rule_definitions')->where('id', $id)->first(), 201);
    }

    /**
     * Dry-run evaluation (08 §8)
     */
    public function evaluate(Request $request, string $id): JsonResponse
    {
        $rule = DB::table('rule_definitions')->where('id', $id)->first();
        if (!$rule) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $data = $request->validate(['data' => 'required|array'])['data'];

        $result = $this->ruleEngine->evaluate(
            category: $rule->category,
            branchId: $rule->scope_branch_id,
            data: $data,
            dryRun: true,
        );

        return response()->json($result);
    }
}

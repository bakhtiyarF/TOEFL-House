<?php

namespace App\Modules\FinancePayroll\Http\Controllers;

use App\Modules\Iam\Services\BranchScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BudgetController extends Controller
{
    public function __construct(
        private BranchScopeService $branchScopeService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $scope = $this->branchScopeService->resolve($request->user(), $request->query('branch_id', 'all'));

        $query = DB::table('budget_lines')->orderBy('name');
        if (!$scope['isAll']) {
            $query->where('branch_id', $scope['branchId']);
        }

        $lines = $query->get()->map(function ($line) {
            $allocated = (float)$line->allocated_amount;
            $spent = (float)$line->current_amount;
            return [
                ...((array)$line),
                'utilization_percent' => $allocated > 0 ? round(($spent / $allocated) * 100, 1) : 0,
                'remaining' => $allocated - $spent,
            ];
        });

        return response()->json($lines);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'purpose' => 'required|string|max:255',
            'allocated_amount' => 'required|numeric|min:0',
            'cost_type' => 'in:fixed,variable',
            'is_marketing' => 'boolean',
            'branch_id' => 'required|uuid|exists:branches,id',
        ]);

        $id = Str::uuid()->toString();
        DB::table('budget_lines')->insert([
            'id' => $id,
            ...$validated,
            'current_amount' => 0,
            'is_marketing' => $validated['is_marketing'] ?? false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(DB::table('budget_lines')->where('id', $id)->first(), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $line = DB::table('budget_lines')->where('id', $id)->first();
        if (!$line) return response()->json(['message' => 'Not found'], 404);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'allocated_amount' => 'numeric|min:0',
            'cost_type' => 'in:fixed,variable',
        ]);

        DB::table('budget_lines')->where('id', $id)->update([...$validated, 'updated_at' => now()]);
        return response()->json(DB::table('budget_lines')->where('id', $id)->first());
    }

    /**
     * Budget overview for BOS dashboard (07 §8)
     */
    public function overview(Request $request): JsonResponse
    {
        $branchId = $request->user()->branch_id;

        $lines = DB::table('budget_lines')->where('branch_id', $branchId)->get();
        $totalAllocated = $lines->sum('allocated_amount');
        $totalSpent = $lines->sum('current_amount');

        $fixedCosts = $lines->where('cost_type', 'fixed')->sum('allocated_amount');
        $reserveFundTarget = $fixedCosts * 6; // 07 §5 — 6 months of fixed costs
        $savingBalance = (float)(DB::table('system_settings')->where('key', 'saving_balance')->value('value') ?? 0);
        $reserveFundMet = $savingBalance >= $reserveFundTarget;

        return response()->json([
            'total_allocated' => $totalAllocated,
            'total_spent' => $totalSpent,
            'remaining' => $totalAllocated - $totalSpent,
            'utilization_percent' => $totalAllocated > 0 ? round(($totalSpent / $totalAllocated) * 100, 1) : 0,
            'reserve_fund_target' => $reserveFundTarget,
            'saving_balance' => $savingBalance,
            'reserve_fund_met' => $reserveFundMet,
            'reserve_fund_percent' => $reserveFundTarget > 0 ? round(($savingBalance / $reserveFundTarget) * 100, 1) : 0,
        ]);
    }
}

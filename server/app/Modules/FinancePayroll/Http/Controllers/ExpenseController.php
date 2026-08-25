<?php

namespace App\Modules\FinancePayroll\Http\Controllers;

use App\Modules\Iam\Services\BranchScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExpenseController extends Controller
{
    public function __construct(
        private BranchScopeService $branchScopeService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $scope = $this->branchScopeService->resolve($request->user(), $request->query('branch_id', 'all'));

        $query = DB::table('expense_requests')
            ->when($request->query('status'), fn($q, $s) => $q->where('status', $s))
            ->orderByDesc('date');

        if (!$scope['isAll']) {
            $query->where('branch_id', $scope['branchId']);
        }

        $expenses = $query->get()->map(function ($exp) {
            return [
                ...((array)$exp),
                'budget_line_name' => DB::table('budget_lines')->where('id', $exp->budget_line_id)->value('name'),
            ];
        });

        return response()->json($expenses);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'budget_line_id' => 'nullable|uuid|exists:budget_lines,id',
            'expense_kind' => 'in:recurring_bill,one_time_purchase,maintenance,other',
            'payment_method' => 'nullable|in:cash,card,bank_transfer',
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'branch_id' => 'required|uuid|exists:branches,id',
        ]);

        if (!$this->branchScopeService->canAccessBranch($request->user(), $validated['branch_id'])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $id = Str::uuid()->toString();

        DB::table('expense_requests')->insert([
            'id' => $id,
            'title' => $validated['title'],
            'amount' => $validated['amount'],
            'budget_line_id' => $validated['budget_line_id'] ?? null,
            'requester' => $request->user()->full_name,
            'status' => 'pending',
            'date' => $validated['date'],
            'expense_kind' => $validated['expense_kind'] ?? 'other',
            'payment_method' => $validated['payment_method'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'branch_id' => $validated['branch_id'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(DB::table('expense_requests')->where('id', $id)->first(), 201);
    }

    public function approve(Request $request, string $id): JsonResponse
    {
        $expense = DB::table('expense_requests')->where('id', $id)->first();
        if (!$expense) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if (!$this->branchScopeService->canAccessBranch($request->user(), $expense->branch_id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        DB::transaction(function () use ($expense, $request, $id) {
            DB::table('expense_requests')->where('id', $id)->update([
                'status' => 'approved',
                'approved_by' => $request->user()->id,
                'updated_at' => now(),
            ]);

            // Create financial transaction (expense)
            DB::table('financial_transactions')->insert([
                'id' => Str::uuid()->toString(),
                'type' => 'expense',
                'category' => 'expense_request',
                'amount' => $expense->amount,
                'date' => now()->toDateString(),
                'description' => "Approved: {$expense->title}",
                'reference_id' => $id,
                'operator_name' => $request->user()->full_name,
                'branch_id' => $expense->branch_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update budget line spent amount
            if ($expense->budget_line_id) {
                DB::table('budget_lines')
                    ->where('id', $expense->budget_line_id)
                    ->increment('current_amount', $expense->amount);
            }
        });

        return response()->json(['message' => 'Expense approved', 'id' => $id]);
    }

    public function reject(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'reject_reason' => 'nullable|string|max:500',
        ]);

        $expense = DB::table('expense_requests')->where('id', $id)->first();
        if (!$expense) {
            return response()->json(['message' => 'Not found'], 404);
        }

        DB::table('expense_requests')->where('id', $id)->update([
            'status' => 'rejected',
            'reject_reason' => $validated['reject_reason'] ?? null,
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Expense rejected']);
    }
}

<?php

namespace App\Modules\FinancePayroll\Http\Controllers;

use App\Modules\Iam\Services\BranchScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    public function __construct(
        private BranchScopeService $branchScopeService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $scope = $this->branchScopeService->resolve($request->user(), $request->query('branch_id', 'all'));

        $query = DB::table('invoices')
            ->leftJoin('students', 'invoices.student_id', '=', 'students.id')
            ->select('invoices.*', 'students.full_name as student_name')
            ->when($request->query('status'), fn($q, $s) => $q->where('invoices.status', $s))
            ->orderByDesc('invoices.issue_date');

        if (!$scope['isAll']) {
            $query->where('invoices.branch_id', $scope['branchId']);
        }

        return response()->json($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|uuid|exists:students,id',
            'due_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'branch_id' => 'required|uuid|exists:branches,id',
        ]);

        if (!$this->branchScopeService->canAccessBranch($request->user(), $validated['branch_id'])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $result = DB::transaction(function () use ($validated, $request) {
            $invoiceId = Str::uuid()->toString();
            $invoiceNumber = 'INV-' . now()->format('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);

            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }

            $discount = $validated['discount_amount'] ?? 0;
            $total = max(0, $subtotal - $discount);

            DB::table('invoices')->insert([
                'id' => $invoiceId,
                'invoice_number' => $invoiceNumber,
                'student_id' => $validated['student_id'],
                'branch_id' => $validated['branch_id'],
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'total_amount' => $total,
                'status' => 'issued',
                'issue_date' => now()->toDateString(),
                'due_date' => $validated['due_date'],
                'notes' => $validated['notes'] ?? null,
                'issued_by' => $request->user()->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($validated['items'] as $item) {
                DB::table('invoice_items')->insert([
                    'id' => Str::uuid()->toString(),
                    'invoice_id' => $invoiceId,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'amount' => $item['quantity'] * $item['unit_price'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return DB::table('invoices')->where('id', $invoiceId)->first();
        });

        return response()->json($result, 201);
    }

    public function markPaid(string $id): JsonResponse
    {
        $invoice = DB::table('invoices')->where('id', $id)->first();
        if (!$invoice) return response()->json(['message' => 'Not found'], 404);

        DB::table('invoices')->where('id', $id)->update([
            'status' => 'paid',
            'paid_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Invoice marked paid']);
    }
}

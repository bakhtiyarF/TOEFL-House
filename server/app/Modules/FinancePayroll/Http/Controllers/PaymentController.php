<?php

namespace App\Modules\FinancePayroll\Http\Controllers;

use App\Modules\FinancePayroll\Services\TuitionCalculationService;
use App\Modules\FinancePayroll\Services\PayrollService;
use App\Modules\Iam\Services\BranchScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct(
        private BranchScopeService $branchScopeService,
        private TuitionCalculationService $tuitionService,
        private PayrollService $payrollService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $scope = $this->branchScopeService->resolve($request->user(), $request->query('branch_id', 'all'));

        $query = DB::table('payments')
            ->when($request->query('student_id'), fn($q, $id) => $q->where('student_id', $id))
            ->when($request->query('category'), fn($q, $cat) => $q->where('category', $cat))
            ->when($request->query('from'), fn($q, $d) => $q->where('date', '>=', $d))
            ->when($request->query('to'), fn($q, $d) => $q->where('date', '<=', $d))
            ->orderByDesc('date');

        if (!$scope['isAll']) {
            $query->where('branch_id', $scope['branchId']);
        }

        return response()->json($query->get());
    }

    /**
     * Record a payment — always writes a matching financial_transactions row
     * in the same DB transaction (02 §9, 07 §10)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => 'nullable|uuid|exists:students,id',
            'invoice_id' => 'nullable|uuid|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'payment_method' => 'required|in:cash,card,bank_transfer',
            'category' => 'required|in:fee,book,chapter,exam,card,placement,diploma,other',
            'notes' => 'nullable|string|max:500',
            'semester' => 'nullable|string|max:50',
            'branch_id' => 'required|uuid|exists:branches,id',
        ]);

        if (!$this->branchScopeService->canAccessBranch($request->user(), $validated['branch_id'])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $result = DB::transaction(function () use ($validated, $request) {
            $paymentId = Str::uuid()->toString();

            // Insert payment
            DB::table('payments')->insert([
                'id' => $paymentId,
                'student_id' => $validated['student_id'] ?? null,
                'invoice_id' => $validated['invoice_id'] ?? null,
                'amount' => $validated['amount'],
                'date' => $validated['date'],
                'payment_method' => $validated['payment_method'],
                'status' => 'completed',
                'category' => $validated['category'],
                'notes' => $validated['notes'] ?? null,
                'semester' => $validated['semester'] ?? null,
                'receipt_number' => 'RCP-' . strtoupper(substr(md5($paymentId), 0, 8)),
                'branch_id' => $validated['branch_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Matching financial_transactions row (02 §9 — never one without the other)
            $txId = Str::uuid()->toString();
            DB::table('financial_transactions')->insert([
                'id' => $txId,
                'type' => 'income',
                'category' => $validated['category'],
                'amount' => $validated['amount'],
                'date' => $validated['date'],
                'description' => "Payment: {$validated['category']} — {$validated['amount']} AFN",
                'reference_id' => $paymentId,
                'operator_name' => $request->user()->full_name,
                'branch_id' => $validated['branch_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Apply 5% savings sweep (02 §7.4 #12)
            $savingPercent = (float)(DB::table('system_settings')
                ->where('key', 'daily_saving_percent')->value('value') ?? 5);
            $savingAmount = $validated['amount'] * ($savingPercent / 100);

            if ($savingAmount > 0) {
                DB::table('system_settings')
                    ->where('key', 'saving_balance')
                    ->increment('value', $savingAmount);

                DB::table('financial_transactions')->insert([
                    'id' => Str::uuid()->toString(),
                    'type' => 'saving_transfer',
                    'category' => 'savings',
                    'amount' => $savingAmount,
                    'date' => $validated['date'],
                    'description' => "Auto savings ({$savingPercent}% of income)",
                    'reference_id' => $paymentId,
                    'operator_name' => 'system',
                    'branch_id' => $validated['branch_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return ['payment_id' => $paymentId, 'transaction_id' => $txId, 'saving_amount' => $savingAmount];
        });

        return response()->json($result, 201);
    }

    /**
     * Student finance summary — wraps 02 §6's pipeline
     */
    public function studentFinanceSummary(Request $request, string $studentId): JsonResponse
    {
        $student = DB::table('students')->where('id', $studentId)->first();
        if (!$student) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if (!$this->branchScopeService->canAccessBranch($request->user(), $student->branch_id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Get total paid from payments
        $totalPaid = DB::table('payments')
            ->where('student_id', $studentId)
            ->where('status', 'completed')
            ->sum('amount');

        // Get gross tuition from active enrollments
        $enrollments = DB::table('enrollments')
            ->where('student_id', $studentId)
            ->where('status', 'active')
            ->get();

        $grossTuition = 0;
        foreach ($enrollments as $enrollment) {
            $snapshot = json_decode($enrollment->fee_snapshot_json, true);
            $grossTuition += collect($snapshot['fee_rules'] ?? [])
                ->where('fee_type', 'semester')
                ->sum('amount');
        }

        $summary = $this->tuitionService->summarizeStudentFinance(
            grossTuition: $grossTuition,
            discountPercent: (float)$student->discount_percent,
            scholarshipPercent: 0, // from enrollments
            amountPaid: (float)$totalPaid,
            branchId: $student->branch_id,
        );

        return response()->json($summary);
    }

    /**
     * Delegated teacher salary endpoints (06 §6)
     */
    public function teacherComputedSalary(Request $request, string $teacherId): JsonResponse
    {
        $periodKey = $request->query('period', now()->format('Y-m'));
        $branchId = $request->user()->branch_id;

        $result = $this->payrollService->computeTeacherDueAmount($teacherId, $periodKey, $branchId);
        return response()->json($result);
    }

    public function payTeacherSalary(Request $request, string $teacherId): JsonResponse
    {
        $validated = $request->validate([
            'period_key' => 'required|string',
            'period_label' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'payment_type' => 'required|in:full,partial,advance',
        ]);

        try {
            $result = $this->payrollService->paySalary(
                teacherId: $teacherId,
                periodKey: $validated['period_key'],
                periodLabel: $validated['period_label'],
                amount: $validated['amount'],
                paymentType: $validated['payment_type'],
                branchId: $request->user()->branch_id,
                operatorName: $request->user()->full_name,
            );

            return response()->json($result, 201);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }

    /**
     * Bulk payroll processing (called from FinancePage "Process Payroll" button)
     * Iterates active teachers in branch, computes due via PayrollService, pays full.
     */
    public function processPayroll(Request $request): JsonResponse
    {
        $period = $request->input('period', now()->format('Y-m'));
        $branchId = $request->user()->branch_id ?? $request->input('branch_id');

        $teachers = DB::table('teachers')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('status', 'active')
            ->get();

        $results = [];
        $totalPaid = 0;

        foreach ($teachers as $teacher) {
            try {
                $due = $this->payrollService->computeTeacherDueAmount($teacher->id, $period, $branchId);
                $amount = (float)($due['dueAmount'] ?? $teacher->base_salary ?? 0);

                if ($amount <= 0) continue;

                $payResult = $this->payrollService->paySalary(
                    teacherId: $teacher->id,
                    periodKey: $period,
                    periodLabel: $period,
                    amount: $amount,
                    paymentType: 'full',
                    branchId: $branchId,
                    operatorName: $request->user()->full_name ?? 'system',
                );

                $results[] = [
                    'teacher_id' => $teacher->id,
                    'amount' => $amount,
                    'ledger_id' => $payResult['ledger_id'] ?? null,
                ];
                $totalPaid += $amount;
            } catch (\Throwable $e) {
                $results[] = [
                    'teacher_id' => $teacher->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'processed' => count($results),
            'total_paid' => $totalPaid,
            'period' => $period,
            'results' => $results,
        ], 200);
    }

    /**
     * Payroll ledger (live history for Finance dashboard)
     */
    public function payrollLedger(Request $request): JsonResponse
    {
        $branchId = $request->user()->branch_id ?? $request->query('branch_id');
        $teacherId = $request->query('teacher_id');

        $query = DB::table('teacher_salary_ledger as l')
            ->join('teachers as t', 'l.teacher_id', '=', 't.id')
            ->select(
                'l.*',
                't.full_name as teacher_name',
                't.salary_type'
            )
            ->orderByDesc('l.paid_at')
            ->limit(50);

        if ($branchId) {
            $query->where('l.branch_id', $branchId);
        }
        if ($teacherId) {
            $query->where('l.teacher_id', $teacherId);
        }

        $ledger = $query->get();

        return response()->json($ledger);
    }
}

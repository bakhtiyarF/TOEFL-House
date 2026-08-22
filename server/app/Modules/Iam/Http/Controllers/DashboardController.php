<?php

namespace App\Modules\Iam\Http\Controllers;

use App\Modules\Iam\Services\BranchScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard Controller — Reporting Layer
 *
 * Composes read-only views over module public interfaces (01 §5, 12 §3).
 * This layer owns NO data — every number is read through existing module tables.
 * Widgets are permission-filtered (03 §7).
 */
class DashboardController extends Controller
{
    public function __construct(
        private BranchScopeService $branchScopeService
    ) {}

    /**
     * Aggregate dashboard data for the current user's branch scope
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $scope = $this->branchScopeService->resolve($user, $user->branch_id);
        $branchId = $scope['isAll'] ? null : $scope['branchId'];

        $data = [];

        // Academic stats
        $studentQuery = DB::table('students');
        $classQuery = DB::table('classes');
        if ($branchId) {
            $studentQuery->where('branch_id', $branchId);
            $classQuery->where('branch_id', $branchId);
        }

        $data['students'] = [
            'total' => (clone $studentQuery)->count(),
            'active' => (clone $studentQuery)->where('status', 'active')->count(),
            'graduated' => (clone $studentQuery)->where('status', 'graduated')->count(),
            'new_this_month' => (clone $studentQuery)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];

        $data['classes'] = [
            'total' => (clone $classQuery)->count(),
            'active' => (clone $classQuery)->where('status', 'active')->count(),
            'total_enrolled' => DB::table('student_semesters')
                ->whereIn('class_id', (clone $classQuery)->select('id'))
                ->where('status', 'active')
                ->count(),
        ];

        // Teacher stats
        $teacherQuery = DB::table('teachers');
        if ($branchId) {
            $teacherQuery->where('branch_id', $branchId);
        }
        $data['teachers'] = [
            'total' => (clone $teacherQuery)->count(),
            'active' => (clone $teacherQuery)->where('status', 'active')->count(),
        ];

        // Finance summary (current month)
        $txQuery = DB::table('financial_transactions')
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year);
        if ($branchId) {
            $txQuery->where('branch_id', $branchId);
        }

        $data['finance'] = [
            'monthly_income' => (clone $txQuery)->where('type', 'income')->sum('amount'),
            'monthly_expenses' => (clone $txQuery)->where('type', 'expense')->sum('amount'),
            'pending_payments' => DB::table('payments')
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->where('status', 'pending')
                ->sum('amount'),
        ];
        $data['finance']['net_income'] = $data['finance']['monthly_income'] - $data['finance']['monthly_expenses'];
        $data['finance']['savings'] = round($data['finance']['monthly_income'] * 0.05);

        // CRM / Leads
        $visitorQuery = DB::table('visitors');
        if ($branchId) {
            $visitorQuery->where('branch_id', $branchId);
        }
        $data['leads'] = [
            'total' => (clone $visitorQuery)->count(),
            'new_this_week' => (clone $visitorQuery)
                ->where('created_at', '>=', now()->startOfWeek())
                ->count(),
            'conversions_this_week' => (clone $visitorQuery)
                ->where('status', 'registered')
                ->where('updated_at', '>=', now()->startOfWeek())
                ->count(),
        ];
        $data['leads']['conversion_rate'] = $data['leads']['new_this_week'] > 0
            ? round(($data['leads']['conversions_this_week'] / $data['leads']['new_this_week']) * 100, 1)
            : 0;

        // Inventory
        $bookQuery = DB::table('books');
        if ($branchId) {
            $bookQuery->where('branch_id', $branchId);
        }
        $data['inventory'] = [
            'total_books' => (clone $bookQuery)->count(),
            'total_stock' => (clone $bookQuery)->sum('stock'),
            'out_of_stock' => (clone $bookQuery)->where('stock', 0)->count(),
        ];

        // Recent activity (notifications)
        $data['recent_notifications'] = DB::table('notifications')
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhereNull('user_id');
            })
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // Today's classes
        $data['todays_classes'] = DB::table('classes')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('status', 'active')
            ->limit(5)
            ->get(['id', 'name', 'schedule_time', 'capacity']);

        return response()->json($data);
    }
}

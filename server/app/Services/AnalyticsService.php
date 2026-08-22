<?php

namespace App\Services;

use App\Modules\Academic\Models\Enrollment;
use App\Modules\Academic\Models\Student;
use App\Modules\FinancePayroll\Models\Payment;
use App\Modules\Iam\Models\Branch;
use App\Modules\PeopleHr\Models\Teacher;
use Illuminate\Support\Facades\DB;

/**
 * Analytics Service
 *
 * Provides analytics and business intelligence data.
 */
class AnalyticsService
{
    /**
     * Get dashboard overview statistics.
     */
    public function getDashboardOverview(?string $branchId = null): array
    {
        $query = Student::query();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $totalStudents = $query->count();
        $activeStudents = (clone $query)->where('status', 'active')->count();
        $newStudentsThisMonth = (clone $query)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $teacherQuery = Teacher::query();

        if ($branchId) {
            $teacherQuery->where('branch_id', $branchId);
        }

        $totalTeachers = $teacherQuery->count();
        $activeTeachers = (clone $teacherQuery)->where('status', 'active')->count();

        $paymentQuery = Payment::query()
            ->where('status', 'completed');

        if ($branchId) {
            $paymentQuery->where('branch_id', $branchId);
        }

        $totalRevenue = $paymentQuery->sum('amount');
        $revenueThisMonth = (clone $paymentQuery)
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount');

        $enrollmentQuery = Enrollment::query()
            ->where('status', 'active');

        if ($branchId) {
            $enrollmentQuery->whereHas('student', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        $activeEnrollments = $enrollmentQuery->count();

        return [
            'students' => [
                'total' => $totalStudents,
                'active' => $activeStudents,
                'new_this_month' => $newStudentsThisMonth,
            ],
            'teachers' => [
                'total' => $totalTeachers,
                'active' => $activeTeachers,
            ],
            'revenue' => [
                'total' => $totalRevenue,
                'this_month' => $revenueThisMonth,
            ],
            'enrollments' => [
                'active' => $activeEnrollments,
            ],
        ];
    }

    /**
     * Get revenue analytics.
     */
    public function getRevenueAnalytics(?string $branchId = null, int $months = 12): array
    {
        $query = Payment::query()
            ->where('status', 'completed')
            ->where('payment_date', '>=', now()->subMonths($months));

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $revenueByMonth = $query->selectRaw('DATE_FORMAT(payment_date, "%Y-%m") as month, SUM(amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => $item->month,
                    'total' => (float)$item->total,
                ];
            })
            ->toArray();

        $revenueByCategory = $query->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $item->category,
                    'total' => (float)$item->total,
                ];
            })
            ->toArray();

        $averagePayment = $query->avg('amount') ?? 0;
        $totalPayments = $query->count();

        return [
            'by_month' => $revenueByMonth,
            'by_category' => $revenueByCategory,
            'average_payment' => (float)$averagePayment,
            'total_payments' => $totalPayments,
            'total_revenue' => array_sum(array_column($revenueByMonth, 'total')),
        ];
    }

    /**
     * Get enrollment analytics.
     */
    public function getEnrollmentAnalytics(?string $branchId = null, int $months = 12): array
    {
        $query = Enrollment::query()
            ->where('created_at', '>=', now()->subMonths($months));

        if ($branchId) {
            $query->whereHas('student', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        $enrollmentsByMonth = $query->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => $item->month,
                    'count' => (int)$item->count,
                ];
            })
            ->toArray();

        $enrollmentsByStatus = $query->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->map(function ($item) {
                return [
                    'status' => $item->status,
                    'count' => (int)$item->count,
                ];
            })
            ->toArray();

        $retentionRate = $this->calculateRetentionRate($branchId);

        return [
            'by_month' => $enrollmentsByMonth,
            'by_status' => $enrollmentsByStatus,
            'retention_rate' => $retentionRate,
            'total_enrollments' => array_sum(array_column($enrollmentsByMonth, 'count')),
        ];
    }

    /**
     * Calculate retention rate.
     */
    protected function calculateRetentionRate(?string $branchId = null): float
    {
        $query = Enrollment::query();

        if ($branchId) {
            $query->whereHas('student', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        $totalEnrollments = (clone $query)->count();
        $completedEnrollments = (clone $query)->where('status', 'completed')->count();

        return $totalEnrollments > 0 ? round(($completedEnrollments / $totalEnrollments) * 100, 2) : 0;
    }

    /**
     * Get attendance analytics.
     */
    public function getAttendanceAnalytics(?string $branchId = null, int $days = 30): array
    {
        $query = DB::table('rosters')
            ->join('sessions', 'rosters.session_id', '=', 'sessions.id')
            ->join('classes', 'sessions.class_id', '=', 'classes.id')
            ->where('sessions.session_date', '>=', now()->subDays($days));

        if ($branchId) {
            $query->where('classes.branch_id', $branchId);
        }

        $totalRecords = $query->count();
        $presentRecords = (clone $query)->where('rosters.attendance_status', 'present')->count();
        $absentRecords = (clone $query)->where('rosters.attendance_status', 'absent')->count();
        $lateRecords = (clone $query)->where('rosters.attendance_status', 'late')->count();

        $attendanceRate = $totalRecords > 0 ? round(($presentRecords / $totalRecords) * 100, 2) : 0;

        $attendanceByDay = $query->selectRaw('DATE(sessions.session_date) as date, COUNT(*) as total, SUM(CASE WHEN rosters.attendance_status = "present" THEN 1 ELSE 0 END) as present')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                $rate = $item->total > 0 ? round(($item->present / $item->total) * 100, 2) : 0;
                return [
                    'date' => $item->date,
                    'total' => (int)$item->total,
                    'present' => (int)$item->present,
                    'rate' => $rate,
                ];
            })
            ->toArray();

        return [
            'overall_rate' => $attendanceRate,
            'total_records' => $totalRecords,
            'present' => $presentRecords,
            'absent' => $absentRecords,
            'late' => $lateRecords,
            'by_day' => $attendanceByDay,
        ];
    }

    /**
     * Get student performance analytics.
     */
    public function getStudentPerformanceAnalytics(?string $branchId = null): array
    {
        $query = DB::table('grades')
            ->join('students', 'grades.student_id', '=', 'students.id');

        if ($branchId) {
            $query->where('students.branch_id', $branchId);
        }

        $averageGrade = $query->avg('grades.percentage') ?? 0;
        $totalGrades = $query->count();

        $gradeDistribution = $query->selectRaw('
            CASE 
                WHEN grades.percentage >= 90 THEN "A"
                WHEN grades.percentage >= 80 THEN "B"
                WHEN grades.percentage >= 70 THEN "C"
                WHEN grades.percentage >= 60 THEN "D"
                ELSE "F"
            END as grade,
            COUNT(*) as count
        ')
        ->groupBy('grade')
        ->get()
        ->map(function ($item) {
            return [
                'grade' => $item->grade,
                'count' => (int)$item->count,
            ];
        })
        ->toArray();

        $topStudents = $query->select('students.id', 'students.full_name', DB::raw('AVG(grades.percentage) as average'))
            ->groupBy('students.id', 'students.full_name')
            ->orderByDesc('average')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->full_name,
                    'average' => round((float)$item->average, 2),
                ];
            })
            ->toArray();

        return [
            'average_grade' => round((float)$averageGrade, 2),
            'total_grades' => $totalGrades,
            'grade_distribution' => $gradeDistribution,
            'top_students' => $topStudents,
        ];
    }

    /**
     * Get branch comparison analytics.
     */
    public function getBranchComparisonAnalytics(): array
    {
        $branches = Branch::where('status', 'active')->get();

        $comparison = $branches->map(function ($branch) {
            $studentCount = Student::where('branch_id', $branch->id)->count();
            $revenue = Payment::where('branch_id', $branch->id)
                ->where('status', 'completed')
                ->whereMonth('payment_date', now()->month)
                ->whereYear('payment_date', now()->year)
                ->sum('amount');

            return [
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'student_count' => $studentCount,
                'monthly_revenue' => (float)$revenue,
            ];
        })
        ->sortByDesc('monthly_revenue')
        ->values()
        ->toArray();

        return $comparison;
    }

    /**
     * Get growth metrics.
     */
    public function getGrowthMetrics(?string $branchId = null): array
    {
        $currentMonth = now()->month;
        $lastMonth = now()->subMonth()->month;

        $studentQuery = Student::query();

        if ($branchId) {
            $studentQuery->where('branch_id', $branchId);
        }

        $studentsThisMonth = (clone $studentQuery)
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', now()->year)
            ->count();

        $studentsLastMonth = (clone $studentQuery)
            ->whereMonth('created_at', $lastMonth)
            ->whereYear('created_at', now()->year)
            ->count();

        $studentGrowth = $studentsLastMonth > 0 
            ? round((($studentsThisMonth - $studentsLastMonth) / $studentsLastMonth) * 100, 2)
            : 0;

        $paymentQuery = Payment::query()
            ->where('status', 'completed');

        if ($branchId) {
            $paymentQuery->where('branch_id', $branchId);
        }

        $revenueThisMonth = (clone $paymentQuery)
            ->whereMonth('payment_date', $currentMonth)
            ->whereYear('payment_date', now()->year)
            ->sum('amount');

        $revenueLastMonth = (clone $paymentQuery)
            ->whereMonth('payment_date', $lastMonth)
            ->whereYear('payment_date', now()->year)
            ->sum('amount');

        $revenueGrowth = $revenueLastMonth > 0 
            ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 2)
            : 0;

        return [
            'students' => [
                'this_month' => $studentsThisMonth,
                'last_month' => $studentsLastMonth,
                'growth_rate' => $studentGrowth,
            ],
            'revenue' => [
                'this_month' => $revenueThisMonth,
                'last_month' => $revenueLastMonth,
                'growth_rate' => $revenueGrowth,
            ],
        ];
    }
}

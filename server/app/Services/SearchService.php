<?php

namespace App\Services;

use App\Modules\Academic\Models\Student;
use App\Modules\Academic\Models\AcademicClass;
use App\Modules\PeopleHr\Models\Teacher;
use App\Modules\CrmEnrollment\Models\Visitor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Advanced Search Service
 *
 * Provides powerful search capabilities across multiple entities
 * with filtering, sorting, and pagination support.
 */
class SearchService
{
    /**
     * Search students with advanced filters
     */
    public function searchStudents(array $filters): LengthAwarePaginator
    {
        $query = Student::with(['branch', 'currentEnrollment.class', 'currentEnrollment.programVersion']);

        // Text search
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Branch filter
        if (!empty($filters['branch_id'])) {
            $query->byBranch($filters['branch_id']);
        }

        // Gender filter
        if (!empty($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }

        // Registration date range
        if (!empty($filters['registered_from'])) {
            $query->where('registration_date', '>=', $filters['registered_from']);
        }
        if (!empty($filters['registered_to'])) {
            $query->where('registration_date', '<=', $filters['registered_to']);
        }

        // Discount filter
        if (isset($filters['has_discount'])) {
            if ($filters['has_discount']) {
                $query->where('discount_percent', '>', 0);
            } else {
                $query->where('discount_percent', 0);
            }
        }

        // Payment status filter
        if (!empty($filters['payment_status'])) {
            $query->whereHas('payments', function ($q) use ($filters) {
                if ($filters['payment_status'] === 'has_payments') {
                    $q->where('status', 'completed');
                } elseif ($filters['payment_status'] === 'no_payments') {
                    // Will be handled with left join
                }
            });
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        // Pagination
        $perPage = $filters['per_page'] ?? 20;

        return $query->paginate($perPage);
    }

    /**
     * Search classes with advanced filters
     */
    public function searchClasses(array $filters): LengthAwarePaginator
    {
        $query = AcademicClass::with(['branch', 'teacher', 'program', 'level']);

        // Text search
        if (!empty($filters['search'])) {
            $query->where('name', 'like', "%{$filters['search']}%");
        }

        // Status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Branch filter
        if (!empty($filters['branch_id'])) {
            $query->byBranch($filters['branch_id']);
        }

        // Teacher filter
        if (!empty($filters['teacher_id'])) {
            $query->byTeacher($filters['teacher_id']);
        }

        // Program filter
        if (!empty($filters['program_id'])) {
            $query->where('program_id', $filters['program_id']);
        }

        // Level filter
        if (!empty($filters['level'])) {
            $query->where('level', $filters['level']);
        }

        // Gender policy filter
        if (!empty($filters['gender_policy'])) {
            $query->where('gender_policy', $filters['gender_policy']);
        }

        // Capacity filter
        if (isset($filters['min_capacity'])) {
            $query->where('capacity', '>=', $filters['min_capacity']);
        }
        if (isset($filters['max_capacity'])) {
            $query->where('capacity', '<=', $filters['max_capacity']);
        }

        // Date range
        if (!empty($filters['start_date_from'])) {
            $query->where('start_date', '>=', $filters['start_date_from']);
        }
        if (!empty($filters['start_date_to'])) {
            $query->where('start_date', '<=', $filters['start_date_to']);
        }

        // Fill rate filter
        if (isset($filters['min_fill_percent'])) {
            // This requires a subquery or join
            $query->whereRaw('(SELECT COUNT(*) FROM student_semesters WHERE class_id = classes.id AND status = "active") / capacity * 100 >= ?', [$filters['min_fill_percent']]);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        // Pagination
        $perPage = $filters['per_page'] ?? 20;

        return $query->paginate($perPage);
    }

    /**
     * Search teachers with advanced filters
     */
    public function searchTeachers(array $filters): LengthAwarePaginator
    {
        $query = Teacher::with(['branch', 'user']);

        // Text search
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('full_name', 'like', "%{$filters['search']}%")
                  ->orWhere('email', 'like', "%{$filters['search']}%")
                  ->orWhere('phone', 'like', "%{$filters['search']}%")
                  ->orWhere('specialization', 'like', "%{$filters['search']}%");
            });
        }

        // Status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Branch filter
        if (!empty($filters['branch_id'])) {
            $query->byBranch($filters['branch_id']);
        }

        // Salary type filter
        if (!empty($filters['salary_type'])) {
            $query->bySalaryType($filters['salary_type']);
        }

        // Specialization filter
        if (!empty($filters['specialization'])) {
            $query->where('specialization', 'like', "%{$filters['specialization']}%");
        }

        // Performance score range
        if (isset($filters['min_performance'])) {
            $query->where('performance_score', '>=', $filters['min_performance']);
        }
        if (isset($filters['max_performance'])) {
            $query->where('performance_score', '<=', $filters['max_performance']);
        }

        // Salary range
        if (isset($filters['min_salary'])) {
            $query->where('base_salary', '>=', $filters['min_salary']);
        }
        if (isset($filters['max_salary'])) {
            $query->where('base_salary', '<=', $filters['max_salary']);
        }

        // Join date range
        if (!empty($filters['joined_from'])) {
            $query->where('joined_date', '>=', $filters['joined_from']);
        }
        if (!empty($filters['joined_to'])) {
            $query->where('joined_date', '<=', $filters['joined_to']);
        }

        // Has active classes filter
        if (isset($filters['has_active_classes'])) {
            if ($filters['has_active_classes']) {
                $query->has('activeClasses');
            } else {
                $query->doesntHave('activeClasses');
            }
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        // Pagination
        $perPage = $filters['per_page'] ?? 20;

        return $query->paginate($perPage);
    }

    /**
     * Search visitors with advanced filters
     */
    public function searchVisitors(array $filters): LengthAwarePaginator
    {
        $query = Visitor::with(['branch', 'campaign']);

        // Text search
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('full_name', 'like', "%{$filters['search']}%")
                  ->orWhere('phone', 'like', "%{$filters['search']}%")
                  ->orWhere('email', 'like', "%{$filters['search']}%")
                  ->orWhere('serial_no', 'like', "%{$filters['search']}%");
            });
        }

        // Stage filter
        if (!empty($filters['stage'])) {
            $query->where('stage', $filters['stage']);
        }

        // Status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Branch filter
        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        // Source filter
        if (!empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        // Campaign filter
        if (!empty($filters['campaign_id'])) {
            $query->where('campaign_id', $filters['campaign_id']);
        }

        // Placement completed filter
        if (isset($filters['placement_completed'])) {
            if ($filters['placement_completed']) {
                $query->whereNotNull('placement_score');
            } else {
                $query->whereNull('placement_score');
            }
        }

        // Visit date range
        if (!empty($filters['visit_from'])) {
            $query->where('visit_date', '>=', $filters['visit_from']);
        }
        if (!empty($filters['visit_to'])) {
            $query->where('visit_date', '<=', $filters['visit_to']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        // Pagination
        $perPage = $filters['per_page'] ?? 20;

        return $query->paginate($perPage);
    }

    /**
     * Global search across all entities
     */
    public function globalSearch(string $query, int $limit = 10): array
    {
        $results = [
            'students' => Student::search($query)->take($limit)->get(),
            'classes' => AcademicClass::where('name', 'like', "%{$query}%")->take($limit)->get(),
            'teachers' => Teacher::where('full_name', 'like', "%{$query}%")->take($limit)->get(),
            'visitors' => Visitor::where('full_name', 'like', "%{$query}%")->take($limit)->get(),
        ];

        return [
            'query' => $query,
            'total_results' => collect($results)->sum(fn($items) => $items->count()),
            'results' => $results,
        ];
    }
}

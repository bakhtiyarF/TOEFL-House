<?php

namespace App\GraphQL\Resolvers;

use App\Services\AnalyticsService;

/**
 * Analytics Resolver
 *
 * Provides GraphQL resolvers for analytics queries.
 */
class AnalyticsResolver
{
    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Resolve dashboard overview query.
     */
    public function dashboardOverview($root, array $args)
    {
        $branchId = $args['branch_id'] ?? null;
        return $this->analyticsService->getDashboardOverview($branchId);
    }

    /**
     * Resolve revenue analytics query.
     */
    public function revenueAnalytics($root, array $args)
    {
        $branchId = $args['branch_id'] ?? null;
        $months = $args['months'] ?? 12;
        return $this->analyticsService->getRevenueAnalytics($branchId, $months);
    }

    /**
     * Resolve enrollment analytics query.
     */
    public function enrollmentAnalytics($root, array $args)
    {
        $branchId = $args['branch_id'] ?? null;
        $months = $args['months'] ?? 12;
        return $this->analyticsService->getEnrollmentAnalytics($branchId, $months);
    }

    /**
     * Resolve attendance analytics query.
     */
    public function attendanceAnalytics($root, array $args)
    {
        $branchId = $args['branch_id'] ?? null;
        $days = $args['days'] ?? 30;
        return $this->analyticsService->getAttendanceAnalytics($branchId, $days);
    }

    /**
     * Resolve student performance analytics query.
     */
    public function studentPerformanceAnalytics($root, array $args)
    {
        $branchId = $args['branch_id'] ?? null;
        return $this->analyticsService->getStudentPerformanceAnalytics($branchId);
    }

    /**
     * Resolve branch comparison analytics query.
     */
    public function branchComparisonAnalytics($root, array $args)
    {
        return $this->analyticsService->getBranchComparisonAnalytics();
    }

    /**
     * Resolve growth metrics query.
     */
    public function growthMetrics($root, array $args)
    {
        $branchId = $args['branch_id'] ?? null;
        return $this->analyticsService->getGrowthMetrics($branchId);
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Cache Service
 *
 * Provides caching strategies for frequently accessed data.
 * Uses Redis for fast, distributed caching.
 */
class CacheService
{
    /**
     * Default cache TTL (1 hour)
     */
    private const DEFAULT_TTL = 3600;

    /**
     * Cache TTL for student data (30 minutes)
     */
    private const STUDENT_TTL = 1800;

    /**
     * Cache TTL for class data (30 minutes)
     */
    private const CLASS_TTL = 1800;

    /**
     * Cache TTL for teacher data (30 minutes)
     */
    private const TEACHER_TTL = 1800;

    /**
     * Cache TTL for financial data (15 minutes)
     */
    private const FINANCIAL_TTL = 900;

    /**
     * Cache TTL for dashboard data (5 minutes)
     */
    private const DASHBOARD_TTL = 300;

    /**
     * Cache TTL for settings (24 hours)
     */
    private const SETTINGS_TTL = 86400;

    /**
     * Get or set cache value
     */
    public function remember(string $key, int $ttl, callable $callback)
    {
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Forget cache value
     */
    public function forget(string $key): bool
    {
        return Cache::forget($key);
    }

    /**
     * Flush cache by pattern
     */
    public function flushByPattern(string $pattern): void
    {
        // Note: This requires Redis and may not work with all cache drivers
        try {
            $redis = Cache::getRedis();
            $keys = $redis->keys("*{$pattern}*");
            
            if (!empty($keys)) {
                $redis->del($keys);
                Log::info("Flushed " . count($keys) . " cache keys matching pattern: {$pattern}");
            }
        } catch (\Exception $e) {
            Log::warning("Failed to flush cache by pattern: " . $e->getMessage());
        }
    }

    // ── Student Caching ──

    /**
     * Get student with caching
     */
    public function getStudent(string $studentId, callable $callback)
    {
        return $this->remember("student:{$studentId}", self::STUDENT_TTL, $callback);
    }

    /**
     * Forget student cache
     */
    public function forgetStudent(string $studentId): void
    {
        $this->forget("student:{$studentId}");
        $this->flushByPattern("students:branch:");
    }

    /**
     * Get students by branch with caching
     */
    public function getStudentsByBranch(string $branchId, callable $callback)
    {
        return $this->remember("students:branch:{$branchId}", self::STUDENT_TTL, $callback);
    }

    // ── Class Caching ──

    /**
     * Get class with caching
     */
    public function getClass(string $classId, callable $callback)
    {
        return $this->remember("class:{$classId}", self::CLASS_TTL, $callback);
    }

    /**
     * Forget class cache
     */
    public function forgetClass(string $classId): void
    {
        $this->forget("class:{$classId}");
        $this->flushByPattern("classes:branch:");
    }

    /**
     * Get classes by branch with caching
     */
    public function getClassesByBranch(string $branchId, callable $callback)
    {
        return $this->remember("classes:branch:{$branchId}", self::CLASS_TTL, $callback);
    }

    // ── Teacher Caching ──

    /**
     * Get teacher with caching
     */
    public function getTeacher(string $teacherId, callable $callback)
    {
        return $this->remember("teacher:{$teacherId}", self::TEACHER_TTL, $callback);
    }

    /**
     * Forget teacher cache
     */
    public function forgetTeacher(string $teacherId): void
    {
        $this->forget("teacher:{$teacherId}");
        $this->flushByPattern("teachers:branch:");
    }

    /**
     * Get teachers by branch with caching
     */
    public function getTeachersByBranch(string $branchId, callable $callback)
    {
        return $this->remember("teachers:branch:{$branchId}", self::TEACHER_TTL, $callback);
    }

    // ── Financial Caching ──

    /**
     * Get financial summary with caching
     */
    public function getFinancialSummary(string $branchId, string $period, callable $callback)
    {
        return $this->remember("financial:summary:{$branchId}:{$period}", self::FINANCIAL_TTL, $callback);
    }

    /**
     * Forget financial cache
     */
    public function forgetFinancial(string $branchId): void
    {
        $this->flushByPattern("financial:{$branchId}");
    }

    /**
     * Get budget lines with caching
     */
    public function getBudgetLines(string $branchId, callable $callback)
    {
        return $this->remember("budget:lines:{$branchId}", self::FINANCIAL_TTL, $callback);
    }

    // ── Dashboard Caching ──

    /**
     * Get dashboard data with caching
     */
    public function getDashboard(string $branchId, callable $callback)
    {
        return $this->remember("dashboard:{$branchId}", self::DASHBOARD_TTL, $callback);
    }

    /**
     * Forget dashboard cache
     */
    public function forgetDashboard(string $branchId): void
    {
        $this->forget("dashboard:{$branchId}");
    }

    // ── Settings Caching ──

    /**
     * Get system settings with caching
     */
    public function getSettings(callable $callback)
    {
        return $this->remember("settings:system", self::SETTINGS_TTL, $callback);
    }

    /**
     * Forget settings cache
     */
    public function forgetSettings(): void
    {
        $this->forget("settings:system");
    }

    /**
     * Get branch settings with caching
     */
    public function getBranchSettings(string $branchId, callable $callback)
    {
        return $this->remember("settings:branch:{$branchId}", self::SETTINGS_TTL, $callback);
    }

    /**
     * Forget branch settings cache
     */
    public function forgetBranchSettings(string $branchId): void
    {
        $this->forget("settings:branch:{$branchId}");
    }

    // ── Permission Caching ──

    /**
     * Get user permissions with caching
     */
    public function getUserPermissions(string $userId, callable $callback)
    {
        return $this->remember("permissions:user:{$userId}", self::DEFAULT_TTL, $callback);
    }

    /**
     * Forget user permissions cache
     */
    public function forgetUserPermissions(string $userId): void
    {
        $this->forget("permissions:user:{$userId}");
    }

    // ── Utility Methods ──

    /**
     * Check if cache has key
     */
    public function has(string $key): bool
    {
        return Cache::has($key);
    }

    /**
     * Get cache value
     */
    public function get(string $key, $default = null)
    {
        return Cache::get($key, $default);
    }

    /**
     * Set cache value
     */
    public function put(string $key, $value, int $ttl = null): bool
    {
        return Cache::put($key, $value, $ttl ?? self::DEFAULT_TTL);
    }

    /**
     * Increment cache value
     */
    public function increment(string $key, int $value = 1): int
    {
        return Cache::increment($key, $value);
    }

    /**
     * Decrement cache value
     */
    public function decrement(string $key, int $value = 1): int
    {
        return Cache::decrement($key, $value);
    }

    /**
     * Flush entire cache
     */
    public function flush(): bool
    {
        return Cache::flush();
    }
}

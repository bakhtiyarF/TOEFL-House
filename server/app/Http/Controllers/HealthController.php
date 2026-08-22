<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

/**
 * Health Check Controller
 *
 * Provides health status endpoints for monitoring and load balancers.
 * Checks database, cache, and Redis connectivity.
 */
class HealthController extends Controller
{
    /**
     * Basic health check (for load balancers)
     * Returns 200 if application is running
     */
    public function ping(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'service' => config('app.name'),
        ]);
    }

    /**
     * Detailed health check with component status
     */
    public function health(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'redis' => $this->checkRedis(),
        ];

        $allHealthy = collect($checks)->every(fn($check) => $check['status'] === 'healthy');

        $response = [
            'status' => $allHealthy ? 'healthy' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'service' => config('app.name'),
            'version' => config('app.version', '3.0.0'),
            'environment' => config('app.env'),
            'checks' => $checks,
        ];

        $statusCode = $allHealthy ? 200 : 503;

        return response()->json($response, $statusCode);
    }

    /**
     * Readiness probe (for Kubernetes)
     * Checks if application is ready to serve traffic
     */
    public function ready(): JsonResponse
    {
        try {
            // Check database connectivity
            DB::connection()->getPdo();

            return response()->json([
                'status' => 'ready',
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'not_ready',
                'timestamp' => now()->toIso8601String(),
                'reason' => config('app.debug') ? $e->getMessage() : 'Database unavailable',
            ], 503);
        }
    }

    /**
     * Liveness probe (for Kubernetes)
     * Checks if application is alive
     */
    public function live(): JsonResponse
    {
        return response()->json([
            'status' => 'alive',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Check database connectivity
     */
    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $duration = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'healthy',
                'duration_ms' => $duration,
                'message' => 'Database connection successful',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'duration_ms' => null,
                'message' => config('app.debug') ? $e->getMessage() : 'Database connection failed',
            ];
        }
    }

    /**
     * Check cache connectivity
     */
    private function checkCache(): array
    {
        try {
            $start = microtime(true);
            $testKey = 'health_check_' . uniqid();
            Cache::put($testKey, 'test', 10);
            $value = Cache::get($testKey);
            Cache::forget($testKey);
            $duration = round((microtime(true) - $start) * 1000, 2);

            if ($value === 'test') {
                return [
                    'status' => 'healthy',
                    'duration_ms' => $duration,
                    'message' => 'Cache read/write successful',
                ];
            }

            return [
                'status' => 'unhealthy',
                'duration_ms' => $duration,
                'message' => 'Cache read/write failed',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'duration_ms' => null,
                'message' => config('app.debug') ? $e->getMessage() : 'Cache connection failed',
            ];
        }
    }

    /**
     * Check Redis connectivity
     */
    private function checkRedis(): array
    {
        try {
            $start = microtime(true);
            $pong = Redis::ping();
            $duration = round((microtime(true) - $start) * 1000, 2);

            if ($pong === 'PONG' || $pong === '+PONG') {
                return [
                    'status' => 'healthy',
                    'duration_ms' => $duration,
                    'message' => 'Redis connection successful',
                ];
            }

            return [
                'status' => 'unhealthy',
                'duration_ms' => $duration,
                'message' => 'Redis ping failed',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'duration_ms' => null,
                'message' => config('app.debug') ? $e->getMessage() : 'Redis connection failed',
            ];
        }
    }
}

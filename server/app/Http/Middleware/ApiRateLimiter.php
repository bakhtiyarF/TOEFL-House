<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Rate Limiter Middleware
 *
 * Protects API endpoints from abuse with tiered rate limiting:
 * - Auth endpoints: 5 requests/minute (brute force protection)
 * - Write endpoints: 60 requests/minute
 * - Read endpoints: 120 requests/minute
 *
 * Per 13_INFRASTRUCTURE_AND_DEPLOYMENT.md §9 — lightweight protection
 */
class ApiRateLimiter
{
    public function handle(Request $request, Closure $next, string $tier = 'default'): Response
    {
        $limits = [
            'auth' => ['max' => 5, 'decay' => 60],      // 5 per minute
            'write' => ['max' => 60, 'decay' => 60],     // 60 per minute
            'read' => ['max' => 120, 'decay' => 60],     // 120 per minute
            'default' => ['max' => 60, 'decay' => 60],   // 60 per minute
        ];

        $config = $limits[$tier] ?? $limits['default'];
        $key = $tier . ':' . ($request->user()?->id ?? $request->ip());

        if (RateLimiter::tooManyAttempts($key, $config['max'])) {
            $retryAfter = RateLimiter::availableIn($key);

            return response()->json([
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $retryAfter,
            ], 429)->withHeaders([
                'Retry-After' => $retryAfter,
                'X-RateLimit-Limit' => $config['max'],
                'X-RateLimit-Remaining' => 0,
            ]);
        }

        RateLimiter::hit($key, $config['decay']);

        $response = $next($request);

        $response->headers->set('X-RateLimit-Limit', (string)$config['max']);
        $response->headers->set('X-RateLimit-Remaining', (string)RateLimiter::remaining($key, $config['max']));

        return $response;
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Request Logging Middleware
 *
 * Logs all API requests for debugging, auditing, and performance monitoring.
 * Respects privacy by not logging sensitive data (passwords, tokens).
 */
class LogApiRequests
{
    /**
     * Fields to exclude from logging for privacy
     */
    private const SENSITIVE_FIELDS = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
        'api_key',
        'secret',
        'credit_card',
        'cvv',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // Log request before processing
        $this->logRequest($request);

        // Process request
        $response = $next($request);

        // Log response after processing
        $this->logResponse($request, $response, $startTime);

        return $response;
    }

    /**
     * Log incoming request
     */
    private function logRequest(Request $request): void
    {
        $logData = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => $request->user()?->id,
            'api_version' => $request->attributes->get('api_version'),
            'headers' => $this->filterHeaders($request->headers->all()),
        ];

        // Log request body for POST/PUT/PATCH (excluding sensitive fields)
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            $logData['body'] = $this->filterSensitiveData($request->all());
        }

        Log::channel('api')->info('API Request', $logData);
    }

    /**
     * Log outgoing response
     */
    private function logResponse(Request $request, Response $response, float $startTime): void
    {
        $duration = round((microtime(true) - $startTime) * 1000, 2); // milliseconds

        $logData = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'user_id' => $request->user()?->id,
        ];

        // Log level based on status code
        $level = match (true) {
            $response->getStatusCode() >= 500 => 'error',
            $response->getStatusCode() >= 400 => 'warning',
            default => 'info',
        };

        Log::channel('api')->$level('API Response', $logData);
    }

    /**
     * Filter sensitive data from request body
     */
    private function filterSensitiveData(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), self::SENSITIVE_FIELDS)) {
                $data[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $data[$key] = $this->filterSensitiveData($value);
            }
        }

        return $data;
    }

    /**
     * Filter headers to exclude sensitive information
     */
    private function filterHeaders(array $headers): array
    {
        $allowedHeaders = [
            'content-type',
            'accept',
            'user-agent',
            'x-api-version',
            'x-request-id',
        ];

        $filtered = [];
        foreach ($headers as $key => $values) {
            if (in_array(strtolower($key), $allowedHeaders)) {
                $filtered[$key] = $values;
            }
        }

        return $filtered;
    }
}

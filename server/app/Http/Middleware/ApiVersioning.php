<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Versioning Middleware
 * 
 * Supports API versioning through:
 * 1. URL prefix: /api/v1/...
 * 2. Accept header: Accept: application/vnd.api.v1+json
 * 3. Custom header: X-API-Version: 1
 * 
 * Defaults to v1 if no version specified
 */
class ApiVersioning
{
    private const SUPPORTED_VERSIONS = ['v1', 'v2'];
    private const DEFAULT_VERSION = 'v1';

    public function handle(Request $request, Closure $next): Response
    {
        $version = $this->resolveVersion($request);
        
        if (!in_array($version, self::SUPPORTED_VERSIONS)) {
            return response()->json([
                'error' => 'Unsupported API version',
                'message' => "API version '{$version}' is not supported. Supported versions: " . implode(', ', self::SUPPORTED_VERSIONS),
                'supported_versions' => self::SUPPORTED_VERSIONS,
            ], 400);
        }

        // Store version in request for controllers to access
        $request->attributes->set('api_version', $version);

        // Add version header to response
        $response = $next($request);
        $response->headers->set('X-API-Version', $version);

        return $response;
    }

    private function resolveVersion(Request $request): string
    {
        // 1. Check URL prefix (e.g., /api/v1/...)
        $path = $request->path();
        if (preg_match('#^api/(v\d+)/#', $path, $matches)) {
            return $matches[1];
        }

        // 2. Check Accept header (e.g., application/vnd.api.v1+json)
        $accept = $request->header('Accept', '');
        if (preg_match('/application\/vnd\.api\.(v\d+)\+json/', $accept, $matches)) {
            return $matches[1];
        }

        // 3. Check custom header (X-API-Version: v1)
        $headerVersion = $request->header('X-API-Version');
        if ($headerVersion && preg_match('/^v\d+$/', $headerVersion)) {
            return $headerVersion;
        }

        // 4. Default to v1
        return self::DEFAULT_VERSION;
    }
}

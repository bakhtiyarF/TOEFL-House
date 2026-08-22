<?php

namespace App\Http\Transformers;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * API Response Transformer
 *
 * Provides consistent JSON response structure across all API endpoints.
 * Includes metadata, pagination info, and standardized error format.
 */
class ApiResponse
{
    /**
     * Success response with data
     */
    public static function success(
        $data = null,
        string $message = 'Operation completed successfully',
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Success response for created resources
     */
    public static function created($data, string $message = 'Resource created successfully'): JsonResponse
    {
        return self::success($data, $message, 201);
    }

    /**
     * Success response for deleted resources
     */
    public static function deleted(string $message = 'Resource deleted successfully'): JsonResponse
    {
        return self::success(null, $message, 204);
    }

    /**
     * Error response
     */
    public static function error(
        string $message,
        string $errorCode = 'ERROR',
        int $statusCode = 400,
        array $errors = [],
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
            'error_code' => $errorCode,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Validation error response
     */
    public static function validationError(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return self::error($message, 'VALIDATION_FAILED', 422, $errors);
    }

    /**
     * Not found error response
     */
    public static function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return self::error($message, 'NOT_FOUND', 404);
    }

    /**
     * Unauthorized error response
     */
    public static function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return self::error($message, 'UNAUTHORIZED', 401);
    }

    /**
     * Forbidden error response
     */
    public static function forbidden(string $message = 'Access denied'): JsonResponse
    {
        return self::error($message, 'FORBIDDEN', 403);
    }

    /**
     * Server error response
     */
    public static function serverError(string $message = 'Internal server error'): JsonResponse
    {
        return self::error($message, 'SERVER_ERROR', 500);
    }

    /**
     * Paginated response with metadata
     */
    public static function paginated(LengthAwarePaginator $paginator, string $message = 'Data retrieved successfully'): JsonResponse
    {
        return self::success(
            $paginator->items(),
            $message,
            200,
            [
                'pagination' => [
                    'total' => $paginator->total(),
                    'count' => $paginator->count(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'total_pages' => $paginator->lastPage(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                    'has_more' => $paginator->hasMorePages(),
                ],
                'links' => [
                    'first' => $paginator->url(1),
                    'last' => $paginator->url($paginator->lastPage()),
                    'prev' => $paginator->previousPageUrl(),
                    'next' => $paginator->nextPageUrl(),
                ],
            ]
        );
    }

    /**
     * Collection response with optional metadata
     */
    public static function collection(Collection $collection, string $message = 'Data retrieved successfully', array $meta = []): JsonResponse
    {
        $meta['count'] = $collection->count();

        return self::success($collection->values(), $message, 200, $meta);
    }

    /**
     * Single resource response
     */
    public static function resource($resource, string $message = 'Resource retrieved successfully'): JsonResponse
    {
        return self::success($resource, $message);
    }

    /**
     * Accepted response for async operations
     */
    public static function accepted(string $message = 'Request accepted and processing', array $meta = []): JsonResponse
    {
        return self::success(null, $message, 202, $meta);
    }

    /**
     * No content response
     */
    public static function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }
}

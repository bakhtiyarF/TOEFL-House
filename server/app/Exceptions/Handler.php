<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Global Exception Handler
 *
 * Provides consistent JSON error responses for API requests
 * Per 03_DESIGN_SYSTEM_AND_UX_STANDARDS.md §6: human, specific, actionable errors
 */
class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Render all exceptions as JSON for API requests
        $this->renderable(function (Throwable $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return $this->handleApiException($e, $request);
            }
        });
    }

    /**
     * Handle API exceptions with consistent JSON responses
     */
    private function handleApiException(Throwable $e, $request)
    {
        $response = [
            'success' => false,
            'message' => $this->getExceptionMessage($e),
            'error_code' => $this->getErrorCode($e),
        ];

        // Add validation errors if applicable
        if ($e instanceof ValidationException) {
            $response['errors'] = $e->errors();
            $response['message'] = 'Validation failed';
        }

        // Add debug info in non-production
        if (config('app.debug')) {
            $response['exception'] = get_class($e);
            $response['file'] = $e->getFile();
            $response['line'] = $e->getLine();
            $response['trace'] = collect($e->getTrace())->take(5)->toArray();
        }

        $statusCode = $this->getStatusCode($e);

        return response()->json($response, $statusCode);
    }

    /**
     * Get user-friendly exception message
     */
    private function getExceptionMessage(Throwable $e): string
    {
        return match (true) {
            $e instanceof AuthenticationException => 'Unauthenticated. Please log in to access this resource.',
            $e instanceof ModelNotFoundException => 'The requested resource was not found.',
            $e instanceof NotFoundHttpException => 'The requested endpoint does not exist.',
            $e instanceof ValidationException => 'The given data was invalid.',
            $e instanceof HttpException => $e->getMessage() ?: 'An HTTP error occurred.',
            default => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred. Please try again later.',
        };
    }

    /**
     * Get error code for client handling
     */
    private function getErrorCode(Throwable $e): string
    {
        return match (true) {
            $e instanceof AuthenticationException => 'AUTH_REQUIRED',
            $e instanceof ModelNotFoundException => 'RESOURCE_NOT_FOUND',
            $e instanceof NotFoundHttpException => 'ENDPOINT_NOT_FOUND',
            $e instanceof ValidationException => 'VALIDATION_FAILED',
            $e instanceof HttpException => 'HTTP_ERROR_' . $e->getStatusCode(),
            default => 'INTERNAL_ERROR',
        };
    }

    /**
     * Get HTTP status code for exception
     */
    private function getStatusCode(Throwable $e): int
    {
        return match (true) {
            $e instanceof AuthenticationException => 401,
            $e instanceof ModelNotFoundException => 404,
            $e instanceof NotFoundHttpException => 404,
            $e instanceof ValidationException => 422,
            $e instanceof HttpException => $e->getStatusCode(),
            default => 500,
        };
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Please log in to access this resource.',
                'error_code' => 'AUTH_REQUIRED',
            ], 401);
        }

        return redirect()->guest(route('login'));
    }
}

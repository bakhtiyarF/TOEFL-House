<?php

namespace App\Modules\Iam\Http\Controllers;

use App\Modules\Iam\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => 'required|string|max:50',
            'password' => 'required|string',
        ]);

        $result = $this->authService->login(
            $validated['username'],
            $validated['password']
        );

        if (!$result) {
            return response()->json([
                'message' => 'Invalid username or password',
            ], 401);
        }

        // Sanctum SPA session is managed by middleware
        return response()->json($result);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(null, 204);
    }

    public function me(Request $request): JsonResponse
    {
        $result = $this->authService->me($request->user());
        return response()->json($result);
    }
}

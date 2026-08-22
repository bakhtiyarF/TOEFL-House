<?php

namespace App\Modules\Iam\Services;

use App\Modules\Iam\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Authentication Service
 * Handles login, logout, and session management via Sanctum
 */
class AuthService
{
    public function __construct(
        private PermissionResolutionService $permissionService
    ) {}

    /**
     * Attempt login with username and password
     */
    public function login(string $username, string $password): ?array
    {
        $user = User::where('username', $username)
            ->where('is_active', true)
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return null;
        }

        $user->update([
            'last_login_at' => now(),
            'last_activity_at' => now(),
        ]);

        $permissions = $this->permissionService->resolve($user);

        return [
            'user' => $user,
            'permissions' => array_values($permissions),
        ];
    }

    /**
     * Get authenticated user with resolved permissions
     */
    public function me(User $user): array
    {
        $user->update(['last_activity_at' => now()]);
        $permissions = $this->permissionService->resolve($user);

        return [
            'user' => $user->load('branch'),
            'permissions' => array_values($permissions),
        ];
    }
}

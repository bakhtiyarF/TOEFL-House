<?php

namespace App\Services;

use App\Modules\Iam\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

/**
 * Social Login Service
 *
 * Provides OAuth social login functionality.
 */
class SocialLoginService
{
    /**
     * Supported OAuth providers.
     */
    protected array $providers = ['google', 'facebook', 'github', 'linkedin'];

    /**
     * Get redirect URL for OAuth provider.
     */
    public function getRedirectUrl(string $provider): string
    {
        if (!$this->isProviderSupported($provider)) {
            throw new \InvalidArgumentException("OAuth provider '{$provider}' is not supported.");
        }

        return Socialite::driver($provider)
            ->stateless()
            ->redirect()
            ->getTargetUrl();
    }

    /**
     * Handle OAuth callback and login/create user.
     */
    public function handleCallback(string $provider): User
    {
        if (!$this->isProviderSupported($provider)) {
            throw new \InvalidArgumentException("OAuth provider '{$provider}' is not supported.");
        }

        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();

            // Find or create user
            $user = User::firstOrCreate(
                ['email' => $socialUser->getEmail()],
                [
                    'name' => $socialUser->getName() ?? $socialUser->getNickname(),
                    'email' => $socialUser->getEmail(),
                    'password' => bcrypt(str_random(16)), // Random password for social login users
                    'email_verified_at' => now(),
                    'avatar' => $socialUser->getAvatar(),
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                ]
            );

            // Update provider info if user already exists
            if (!$user->wasRecentlyCreated) {
                $user->update([
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'avatar' => $socialUser->getAvatar() ?? $user->avatar,
                ]);
            }

            // Log the user in
            Auth::login($user);

            Log::info("User logged in via OAuth", [
                'user_id' => $user->id,
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
            ]);

            return $user;
        } catch (\Exception $e) {
            Log::error("OAuth callback failed", [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Link social account to existing user.
     */
    public function linkSocialAccount(User $user, string $provider): User
    {
        if (!$this->isProviderSupported($provider)) {
            throw new \InvalidArgumentException("OAuth provider '{$provider}' is not supported.");
        }

        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();

            // Check if social account is already linked to another user
            $existingUser = User::where('provider', $provider)
                ->where('provider_id', $socialUser->getId())
                ->first();

            if ($existingUser && $existingUser->id !== $user->id) {
                throw new \Exception("This {$provider} account is already linked to another user.");
            }

            // Link social account to user
            $user->update([
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'avatar' => $socialUser->getAvatar() ?? $user->avatar,
            ]);

            Log::info("Social account linked to user", [
                'user_id' => $user->id,
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
            ]);

            return $user;
        } catch (\Exception $e) {
            Log::error("Failed to link social account", [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Unlink social account from user.
     */
    public function unlinkSocialAccount(User $user): User
    {
        // Ensure user has a password before unlinking
        if (!$user->password) {
            throw new \Exception("Cannot unlink social account. User must have a password set.");
        }

        $user->update([
            'provider' => null,
            'provider_id' => null,
        ]);

        Log::info("Social account unlinked from user", [
            'user_id' => $user->id,
        ]);

        return $user;
    }

    /**
     * Check if provider is supported.
     */
    public function isProviderSupported(string $provider): bool
    {
        return in_array($provider, $this->providers);
    }

    /**
     * Get list of supported providers.
     */
    public function getSupportedProviders(): array
    {
        return $this->providers;
    }

    /**
     * Get user's linked social accounts.
     */
    public function getLinkedAccounts(User $user): array
    {
        $accounts = [];

        if ($user->provider && $user->provider_id) {
            $accounts[] = [
                'provider' => $user->provider,
                'provider_id' => $user->provider_id,
                'avatar' => $user->avatar,
            ];
        }

        return $accounts;
    }
}

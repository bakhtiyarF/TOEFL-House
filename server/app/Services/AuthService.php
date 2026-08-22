<?php

namespace App\Services;

use App\Mail\PasswordResetMail;
use App\Mail\VerifyEmailMail;
use App\Mail\TwoFactorCodeMail;
use App\Modules\Iam\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

/**
 * Authentication Service
 *
 * Handles password reset, email verification, and two-factor authentication.
 */
class AuthService
{
    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail(string $email): bool
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return false;
        }

        // Generate secure reset token
        $token = Str::random(64);
        $expirationMinutes = 60;

        // Store token in cache with expiration
        Cache::put(
            "password_reset:{$user->id}",
            ['token' => hash('sha256', $token), 'email' => $email],
            now()->addMinutes($expirationMinutes)
        );

        // Generate reset URL
        $resetUrl = config('app.frontend_url') . "/reset-password?token={$token}&email=" . urlencode($email);

        // Send email
        Mail::to($user->email)->send(new PasswordResetMail($user, $resetUrl, $expirationMinutes));

        return true;
    }

    /**
     * Reset user password
     */
    public function resetPassword(string $email, string $token, string $newPassword): bool
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return false;
        }

        // Verify token
        $cached = Cache::get("password_reset:{$user->id}");

        if (!$cached || !hash_equals($cached['token'], hash('sha256', $token))) {
            return false;
        }

        // Update password
        $user->update([
            'password' => Hash::make($newPassword),
            'must_change_password' => false,
        ]);

        // Clear reset token
        Cache::forget("password_reset:{$user->id}");

        // Invalidate all sessions
        $user->tokens()->delete();

        return true;
    }

    /**
     * Send email verification email
     */
    public function sendVerificationEmail(User $user): bool
    {
        // Generate verification token
        $token = Str::random(64);
        $expirationMinutes = 60;

        // Store token in cache
        Cache::put(
            "email_verification:{$user->id}",
            ['token' => hash('sha256', $token), 'email' => $user->email],
            now()->addMinutes($expirationMinutes)
        );

        // Generate verification URL
        $verificationUrl = config('app.frontend_url') . "/verify-email?token={$token}&id={$user->id}";

        // Send email
        Mail::to($user->email)->send(new VerifyEmailMail($user, $verificationUrl, $expirationMinutes));

        return true;
    }

    /**
     * Verify user email
     */
    public function verifyEmail(string $userId, string $token): bool
    {
        $user = User::find($userId);

        if (!$user) {
            return false;
        }

        // Verify token
        $cached = Cache::get("email_verification:{$user->id}");

        if (!$cached || !hash_equals($cached['token'], hash('sha256', $token))) {
            return false;
        }

        // Mark email as verified
        $user->update([
            'email_verified_at' => now(),
        ]);

        // Clear verification token
        Cache::forget("email_verification:{$user->id}");

        return true;
    }

    /**
     * Generate and send 2FA code
     */
    public function sendTwoFactorCode(User $user): bool
    {
        // Generate 6-digit code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expirationMinutes = 10;

        // Store code in cache
        Cache::put(
            "2fa_code:{$user->id}",
            ['code' => $code],
            now()->addMinutes($expirationMinutes)
        );

        // Send email
        Mail::to($user->email)->send(new TwoFactorCodeMail($user, $code, $expirationMinutes));

        return true;
    }

    /**
     * Verify 2FA code
     */
    public function verifyTwoFactorCode(User $user, string $code): bool
    {
        // Get cached code
        $cached = Cache::get("2fa_code:{$user->id}");

        if (!$cached || $cached['code'] !== $code) {
            return false;
        }

        // Clear code after successful verification
        Cache::forget("2fa_code:{$user->id}");

        return true;
    }

    /**
     * Enable 2FA for user
     */
    public function enableTwoFactor(User $user): bool
    {
        $user->update([
            'two_factor_enabled' => true,
        ]);

        return true;
    }

    /**
     * Disable 2FA for user
     */
    public function disableTwoFactor(User $user): bool
    {
        $user->update([
            'two_factor_enabled' => false,
        ]);

        // Clear any pending codes
        Cache::forget("2fa_code:{$user->id}");

        return true;
    }

    /**
     * Check if user requires 2FA
     */
    public function requiresTwoFactor(User $user): bool
    {
        return $user->two_factor_enabled === true;
    }
}

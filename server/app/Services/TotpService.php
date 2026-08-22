<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use PragmaRX\Google2FA\Google2FA;

/**
 * TOTP Service
 *
 * Provides Time-based One-Time Password (TOTP) two-factor authentication.
 */
class TotpService
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Generate a new secret key.
     */
    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    /**
     * Get QR code URL for secret.
     */
    public function getQrCodeUrl(string $companyName, string $userEmail, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl(
            $companyName,
            $userEmail,
            $secret
        );
    }

    /**
     * Generate QR code image (inline).
     */
    public function generateQrCodeInline(string $companyName, string $userEmail, string $secret): string
    {
        $qrCodeUrl = $this->getQrCodeUrl($companyName, $userEmail, $secret);
        
        // Generate QR code using BaconQrCode
        $qrCode = \BaconQrCode\Writer::writeString(
            new \BaconQrCode\Renderer\ImageRenderer(
                new \BaconQrCode\Renderer\RendererStyle\RendererStyle(300),
                new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
            ),
            $qrCodeUrl
        );

        return 'data:image/svg+xml;base64,' . base64_encode($qrCode);
    }

    /**
     * Verify a TOTP code.
     */
    public function verify(string $secret, string $code, int $window = 1): bool
    {
        try {
            $timestamp = $this->google2fa->verifyKey($secret, $code, $window);
            return $timestamp !== false;
        } catch (\Exception $e) {
            Log::error("TOTP verification failed", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Verify and invalidate a TOTP code (prevent reuse).
     */
    public function verifyAndInvalidate(string $userId, string $secret, string $code): bool
    {
        $cacheKey = 'totp_used:' . $userId . ':' . $code;

        // Check if code was already used
        if (cache()->has($cacheKey)) {
            Log::warning("TOTP code reuse attempt", ['user_id' => $userId]);
            return false;
        }

        // Verify the code
        if (!$this->verify($secret, $code)) {
            return false;
        }

        // Mark code as used (valid for 2 minutes to prevent replay attacks)
        cache()->put($cacheKey, true, now()->addMinutes(2));

        return true;
    }

    /**
     * Generate backup codes.
     */
    public function generateBackupCodes(int $count = 8): array
    {
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4)));
        }

        return $codes;
    }

    /**
     * Hash backup codes for storage.
     */
    public function hashBackupCodes(array $codes): array
    {
        return array_map(function ($code) {
            return hash('sha256', $code);
        }, $codes);
    }

    /**
     * Verify a backup code.
     */
    public function verifyBackupCode(string $code, array $hashedCodes): ?int
    {
        $codeHash = hash('sha256', $code);

        foreach ($hashedCodes as $index => $hashedCode) {
            if (hash_equals($hashedCode, $codeHash)) {
                return $index; // Return index so it can be removed
            }
        }

        return null;
    }

    /**
     * Encrypt secret for storage.
     */
    public function encryptSecret(string $secret): string
    {
        return Crypt::encryptString($secret);
    }

    /**
     * Decrypt secret from storage.
     */
    public function decryptSecret(string $encryptedSecret): string
    {
        return Crypt::decryptString($encryptedSecret);
    }

    /**
     * Get current TOTP code (for testing).
     */
    public function getCurrentCode(string $secret): string
    {
        return $this->google2fa->getCurrentOtp($secret);
    }

    /**
     * Get time until next code.
     */
    public function getTimeUntilNextCode(): int
    {
        return $this->google2fa->getTimestamp() % 30;
    }

    /**
     * Validate TOTP secret format.
     */
    public function isValidSecret(string $secret): bool
    {
        try {
            // Try to generate a code with the secret
            $this->google2fa->getCurrentOtp($secret);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Enable 2FA for a user.
     */
    public function enable2FA($user, string $secret, array $backupCodes): bool
    {
        try {
            $user->update([
                'two_factor_secret' => $this->encryptSecret($secret),
                'two_factor_backup_codes' => json_encode($this->hashBackupCodes($backupCodes)),
                'two_factor_enabled' => true,
                'two_factor_enabled_at' => now(),
            ]);

            Log::info("2FA enabled for user", ['user_id' => $user->id]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to enable 2FA", [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Disable 2FA for a user.
     */
    public function disable2FA($user): bool
    {
        try {
            $user->update([
                'two_factor_secret' => null,
                'two_factor_backup_codes' => null,
                'two_factor_enabled' => false,
                'two_factor_disabled_at' => now(),
            ]);

            Log::info("2FA disabled for user", ['user_id' => $user->id]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to disable 2FA", [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Verify user's 2FA code.
     */
    public function verifyUser2FA($user, string $code): bool
    {
        if (!$user->two_factor_enabled || !$user->two_factor_secret) {
            return false;
        }

        $secret = $this->decryptSecret($user->two_factor_secret);

        // Try TOTP code first
        if ($this->verifyAndInvalidate($user->id, $secret, $code)) {
            return true;
        }

        // Try backup code
        $backupCodes = json_decode($user->two_factor_backup_codes, true) ?? [];
        $backupCodeIndex = $this->verifyBackupCode($code, $backupCodes);

        if ($backupCodeIndex !== null) {
            // Remove used backup code
            unset($backupCodes[$backupCodeIndex]);
            $user->update([
                'two_factor_backup_codes' => json_encode(array_values($backupCodes)),
            ]);

            Log::info("Backup code used for 2FA", [
                'user_id' => $user->id,
                'remaining_codes' => count($backupCodes),
            ]);

            return true;
        }

        return false;
    }
}

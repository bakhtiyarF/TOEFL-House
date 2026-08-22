<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Security Service
 *
 * Provides security utilities for input sanitization, data encryption,
 * and protection against common web vulnerabilities.
 */
class SecurityService
{
    /**
     * Sanitize user input to prevent XSS attacks.
     */
    public function sanitizeInput($input)
    {
        if (is_array($input)) {
            return array_map([$this, 'sanitizeInput'], $input);
        }

        if (is_string($input)) {
            // Remove null bytes
            $input = str_replace("\0", '', $input);

            // Convert special characters to HTML entities
            $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);

            // Remove potentially dangerous characters
            $input = preg_replace('/[\x00-\x1F\x7F]/u', '', $input);

            return trim($input);
        }

        return $input;
    }

    /**
     * Sanitize HTML content (allow safe HTML tags).
     */
    public function sanitizeHtml(string $html): string
    {
        // Allowed tags
        $allowedTags = '<p><br><strong><em><u><ol><ul><li><a><blockquote><code><pre><h1><h2><h3><h4><h5><h6>';

        // Strip all tags except allowed ones
        $html = strip_tags($html, $allowedTags);

        // Remove dangerous attributes
        $html = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);
        $html = preg_replace('/\s*javascript\s*:/i', '', $html);
        $html = preg_replace('/\s*vbscript\s*:/i', '', $html);

        // Remove data URIs (can contain malicious code)
        $html = preg_replace('/\s*data\s*:[^"\'>\s]+/i', '', $html);

        return $html;
    }

    /**
     * Encrypt sensitive data.
     */
    public function encrypt($data): string
    {
        try {
            return Crypt::encryptString(json_encode($data));
        } catch (\Exception $e) {
            Log::error("Encryption failed", ['error' => $e->getMessage()]);
            throw new \RuntimeException("Failed to encrypt data");
        }
    }

    /**
     * Decrypt sensitive data.
     */
    public function decrypt(string $encryptedData)
    {
        try {
            $decrypted = Crypt::decryptString($encryptedData);
            return json_decode($decrypted, true);
        } catch (\Exception $e) {
            Log::error("Decryption failed", ['error' => $e->getMessage()]);
            throw new \RuntimeException("Failed to decrypt data");
        }
    }

    /**
     * Hash sensitive data (one-way).
     */
    public function hash(string $data): string
    {
        return hash('sha256', $data . config('app.key'));
    }

    /**
     * Verify hashed data.
     */
    public function verifyHash(string $data, string $hash): bool
    {
        return hash_equals($hash, $this->hash($data));
    }

    /**
     * Generate a secure random token.
     */
    public function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Generate a secure API key.
     */
    public function generateApiKey(): string
    {
        return 'toefl_' . $this->generateToken(48);
    }

    /**
     * Validate and sanitize email address.
     */
    public function sanitizeEmail(string $email): ?string
    {
        $email = trim($email);
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return strtolower($email);
    }

    /**
     * Validate and sanitize URL.
     */
    public function sanitizeUrl(string $url): ?string
    {
        $url = trim($url);
        $url = filter_var($url, FILTER_SANITIZE_URL);

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        // Ensure URL uses HTTPS
        if (strpos($url, 'https://') !== 0) {
            $url = str_replace('http://', 'https://', $url);
        }

        return $url;
    }

    /**
     * Sanitize phone number.
     */
    public function sanitizePhone(string $phone): string
    {
        // Remove all non-digit characters except +
        $phone = preg_replace('/[^\d+]/', '', $phone);

        // Ensure it starts with +
        if (strpos($phone, '+') !== 0 && strlen($phone) > 0) {
            $phone = '+' . $phone;
        }

        return $phone;
    }

    /**
     * Mask sensitive data for display.
     */
    public function maskSensitiveData(string $data, int $visibleChars = 4): string
    {
        $length = strlen($data);

        if ($length <= $visibleChars) {
            return str_repeat('*', $length);
        }

        $masked = str_repeat('*', $length - $visibleChars);
        return $masked . substr($data, -$visibleChars);
    }

    /**
     * Check for SQL injection attempts.
     */
    public function detectSqlInjection(string $input): bool
    {
        $patterns = [
            '/(\b(SELECT|INSERT|UPDATE|DELETE|DROP|UNION|ALTER|CREATE|EXEC)\b)/i',
            '/(\b(OR|AND)\b\s+\d+\s*=\s*\d+)/i',
            '/(\b(OR|AND)\b\s+[\'"].*=[\'"])/i',
            '/(--|#|\/\*|\*\/)/',
            '/(\b(SLEEP|BENCHMARK|WAITFOR)\b)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                Log::warning("Potential SQL injection detected", [
                    'input' => substr($input, 0, 100),
                    'pattern' => $pattern,
                ]);
                return true;
            }
        }

        return false;
    }

    /**
     * Check for XSS attempts.
     */
    public function detectXss(string $input): bool
    {
        $patterns = [
            '/<script\b[^>]*>.*?<\/script>/is',
            '/javascript\s*:/i',
            '/on\w+\s*=/i',
            '/<iframe\b[^>]*>/i',
            '/<object\b[^>]*>/i',
            '/<embed\b[^>]*>/i',
            '/data\s*:[^"\'>\s]+/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                Log::warning("Potential XSS detected", [
                    'input' => substr($input, 0, 100),
                    'pattern' => $pattern,
                ]);
                return true;
            }
        }

        return false;
    }

    /**
     * Rate limit check.
     */
    public function checkRateLimit(string $key, int $maxAttempts, int $decayMinutes): bool
    {
        $cacheKey = 'rate_limit:' . md5($key);
        $attempts = cache()->get($cacheKey, 0);

        if ($attempts >= $maxAttempts) {
            Log::warning("Rate limit exceeded", [
                'key' => $key,
                'attempts' => $attempts,
                'max' => $maxAttempts,
            ]);
            return false;
        }

        cache()->put($cacheKey, $attempts + 1, now()->addMinutes($decayMinutes));
        return true;
    }

    /**
     * Validate file upload.
     */
    public function validateFileUpload($file, array $allowedTypes, int $maxSize): array
    {
        $errors = [];

        // Check file size
        if ($file->getSize() > $maxSize) {
            $errors[] = "File size exceeds maximum allowed size of " . ($maxSize / 1024 / 1024) . "MB";
        }

        // Check MIME type
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, $allowedTypes)) {
            $errors[] = "File type '{$mimeType}' is not allowed. Allowed types: " . implode(', ', $allowedTypes);
        }

        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = array_map(function ($type) {
            $parts = explode('/', $type);
            return $parts[1] ?? '';
        }, $allowedTypes);

        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = "File extension '.{$extension}' is not allowed";
        }

        // Check for malicious file names
        $filename = $file->getClientOriginalName();
        if (preg_match('/[<>:"\/\\|?*\x00-\x1F]/', $filename)) {
            $errors[] = "File name contains invalid characters";
        }

        return $errors;
    }

    /**
     * Generate secure password.
     */
    public function generateSecurePassword(int $length = 16): string
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*()_+-=[]{}|;:,.<>?';

        $allChars = $uppercase . $lowercase . $numbers . $symbols;

        $password = '';

        // Ensure at least one of each type
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];

        // Fill the rest randomly
        for ($i = 4; $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }

        // Shuffle the password
        return str_shuffle($password);
    }

    /**
     * Validate password strength.
     */
    public function validatePasswordStrength(string $password): array
    {
        $errors = [];
        $score = 0;

        // Length check
        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long";
        } else {
            $score += 1;
        }

        if (strlen($password) >= 12) {
            $score += 1;
        }

        // Uppercase check
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        } else {
            $score += 1;
        }

        // Lowercase check
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        } else {
            $score += 1;
        }

        // Number check
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        } else {
            $score += 1;
        }

        // Symbol check
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "Password must contain at least one special character";
        } else {
            $score += 1;
        }

        // Common password check
        $commonPasswords = ['password', '123456', 'qwerty', 'admin', 'letmein', 'welcome'];
        if (in_array(strtolower($password), $commonPasswords)) {
            $errors[] = "Password is too common";
            $score = 0;
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'score' => $score,
            'strength' => $score <= 2 ? 'weak' : ($score <= 4 ? 'medium' : 'strong'),
        ];
    }
}

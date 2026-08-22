<?php

namespace App\Listeners;

use App\Events\FailedLoginAttempt;
use App\Modules\PlatformServices\Models\AuditLog;
use App\Services\SecurityService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Log Failed Login Attempt Listener
 *
 * Logs failed login attempts and implements rate limiting.
 */
class LogFailedLoginAttempt implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct(
        private SecurityService $securityService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(FailedLoginAttempt $event): void
    {
        try {
            // Log the failed attempt
            AuditLog::create([
                'action' => 'failed_login',
                'description' => "Failed login attempt for username: {$event->username}",
                'ip_address' => $event->ipAddress,
                'user_agent' => $event->userAgent,
                'metadata' => [
                    'username' => $event->username,
                    'reason' => $event->reason ?? 'Invalid credentials',
                ],
            ]);

            // Track failed attempts per IP
            if ($event->ipAddress) {
                $cacheKey = 'failed_login_ip:' . md5($event->ipAddress);
                $attempts = Cache::get($cacheKey, 0);
                Cache::put($cacheKey, $attempts + 1, now()->addMinutes(15));

                // Block IP after 10 failed attempts
                if ($attempts >= 10) {
                    $this->securityService->blockIp($event->ipAddress, 'Too many failed login attempts');
                    
                    Log::warning("IP blocked due to failed login attempts", [
                        'ip' => $event->ipAddress,
                        'attempts' => $attempts + 1,
                    ]);
                }
            }

            // Track failed attempts per username
            $cacheKey = 'failed_login_user:' . md5($event->username);
            $attempts = Cache::get($cacheKey, 0);
            Cache::put($cacheKey, $attempts + 1, now()->addMinutes(15));

            Log::warning("Failed login attempt logged", [
                'username' => $event->username,
                'ip' => $event->ipAddress,
                'reason' => $event->reason,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to log failed login attempt", [
                'username' => $event->username,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

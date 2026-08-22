<?php

namespace App\Listeners;

use App\Events\UserLogin;
use App\Modules\PlatformServices\Models\AuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Log User Login Listener
 *
 * Logs user login activity.
 */
class LogUserLogin implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(UserLogin $event): void
    {
        try {
            AuditLog::create([
                'user_id' => $event->user->id,
                'action' => 'login',
                'description' => 'User logged in successfully',
                'ip_address' => $event->ipAddress,
                'user_agent' => $event->userAgent,
                'metadata' => [
                    'user_email' => $event->user->email,
                    'user_name' => $event->user->full_name,
                ],
            ]);

            Log::info("User login logged", [
                'user_id' => $event->user->id,
                'ip' => $event->ipAddress,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to log user login", [
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

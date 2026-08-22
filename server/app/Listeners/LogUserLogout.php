<?php

namespace App\Listeners;

use App\Events\UserLogout;
use App\Modules\PlatformServices\Models\AuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Log User Logout Listener
 *
 * Logs user logout activity.
 */
class LogUserLogout implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(UserLogout $event): void
    {
        try {
            AuditLog::create([
                'user_id' => $event->user->id,
                'action' => 'logout',
                'description' => 'User logged out',
                'metadata' => [
                    'user_email' => $event->user->email,
                    'user_name' => $event->user->full_name,
                ],
            ]);

            Log::info("User logout logged", [
                'user_id' => $event->user->id,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to log user logout", [
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

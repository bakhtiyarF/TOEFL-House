<?php

namespace App\Services;

use App\Modules\Iam\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Notification Preference Service
 *
 * Manages user notification preferences for different channels and event types.
 */
class NotificationPreferenceService
{
    /**
     * Default notification preferences
     */
    private const DEFAULT_PREFERENCES = [
        'email' => [
            'student_registered' => true,
            'payment_received' => true,
            'payment_overdue' => true,
            'enrollment_created' => true,
            'class_assigned' => true,
            'attendance_warning' => true,
            'exam_scheduled' => true,
            'grade_posted' => true,
            'certificate_issued' => true,
            'system_announcements' => true,
            'security_alerts' => true,
        ],
        'sms' => [
            'student_registered' => false,
            'payment_received' => true,
            'payment_overdue' => true,
            'enrollment_created' => false,
            'class_assigned' => false,
            'attendance_warning' => true,
            'exam_scheduled' => true,
            'grade_posted' => false,
            'certificate_issued' => false,
            'system_announcements' => false,
            'security_alerts' => true,
        ],
        'in_app' => [
            'student_registered' => true,
            'payment_received' => true,
            'payment_overdue' => true,
            'enrollment_created' => true,
            'class_assigned' => true,
            'attendance_warning' => true,
            'exam_scheduled' => true,
            'grade_posted' => true,
            'certificate_issued' => true,
            'system_announcements' => true,
            'security_alerts' => true,
        ],
    ];

    /**
     * Get user's notification preferences
     */
    public function getPreferences(User $user): array
    {
        $cacheKey = "notification_prefs:{$user->id}";

        return Cache::remember($cacheKey, 3600, function () use ($user) {
            $stored = $user->notification_preferences ?? [];
            
            // Merge with defaults
            $preferences = self::DEFAULT_PREFERENCES;
            
            foreach ($stored as $channel => $events) {
                if (isset($preferences[$channel])) {
                    $preferences[$channel] = array_merge($preferences[$channel], $events);
                }
            }
            
            return $preferences;
        });
    }

    /**
     * Update user's notification preferences
     */
    public function updatePreferences(User $user, array $preferences): bool
    {
        // Validate preferences structure
        $validated = $this->validatePreferences($preferences);

        // Update user
        $user->update([
            'notification_preferences' => $validated,
        ]);

        // Clear cache
        Cache::forget("notification_prefs:{$user->id}");

        return true;
    }

    /**
     * Check if user should receive notification for event
     */
    public function shouldNotify(User $user, string $channel, string $eventType): bool
    {
        $preferences = $this->getPreferences($user);

        return $preferences[$channel][$eventType] ?? false;
    }

    /**
     * Get channels that should receive notification for event
     */
    public function getChannelsForEvent(User $user, string $eventType): array
    {
        $preferences = $this->getPreferences($user);
        $channels = [];

        foreach (['email', 'sms', 'in_app'] as $channel) {
            if ($preferences[$channel][$eventType] ?? false) {
                $channels[] = $channel;
            }
        }

        return $channels;
    }

    /**
     * Reset preferences to defaults
     */
    public function resetToDefaults(User $user): bool
    {
        $user->update([
            'notification_preferences' => [],
        ]);

        Cache::forget("notification_prefs:{$user->id}");

        return true;
    }

    /**
     * Get all available event types
     */
    public function getEventTypes(): array
    {
        return [
            'student_registered' => 'New Student Registered',
            'payment_received' => 'Payment Received',
            'payment_overdue' => 'Payment Overdue',
            'enrollment_created' => 'New Enrollment Created',
            'class_assigned' => 'Class Assigned',
            'attendance_warning' => 'Attendance Warning',
            'exam_scheduled' => 'Exam Scheduled',
            'grade_posted' => 'Grade Posted',
            'certificate_issued' => 'Certificate Issued',
            'system_announcements' => 'System Announcements',
            'security_alerts' => 'Security Alerts',
        ];
    }

    /**
     * Get all available channels
     */
    public function getChannels(): array
    {
        return [
            'email' => 'Email Notifications',
            'sms' => 'SMS Notifications',
            'in_app' => 'In-App Notifications',
        ];
    }

    /**
     * Validate preferences structure
     */
    private function validatePreferences(array $preferences): array
    {
        $validated = [];
        $validChannels = ['email', 'sms', 'in_app'];
        $validEvents = array_keys($this->getEventTypes());

        foreach ($preferences as $channel => $events) {
            if (!in_array($channel, $validChannels)) {
                continue;
            }

            $validated[$channel] = [];

            if (is_array($events)) {
                foreach ($events as $event => $enabled) {
                    if (in_array($event, $validEvents) && is_bool($enabled)) {
                        $validated[$channel][$event] = $enabled;
                    }
                }
            }
        }

        return $validated;
    }
}

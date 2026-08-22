<?php

namespace App\Listeners;

use App\Events\LeadConverted;
use App\Modules\Iam\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Send Lead Converted Notification Listener
 *
 * Sends notifications when a lead is converted to a student.
 */
class SendLeadConvertedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(LeadConverted $event): void
    {
        $lead = $event->lead;

        try {
            // Notify lead if they have a user account
            if ($lead->user) {
                Notification::create([
                    'user_id' => $lead->user->id,
                    'type' => 'success',
                    'title' => 'Welcome! You Are Now a Student',
                    'message' => "Congratulations! Your lead has been converted and you are now enrolled as a student.",
                    'data' => [
                        'lead_id' => $lead->id,
                        'student_id' => $event->studentId,
                        'lead_name' => $lead->full_name,
                    ],
                    'action_url' => "/students/{$event->studentId}",
                    'read' => false,
                ]);
            }

            // Notify administrators and counselors
            $admins = $lead->branch->users()
                ->whereHas('roles', function ($query) {
                    $query->whereIn('name', ['admin', 'counselor', 'admissions_officer']);
                })
                ->get();

            foreach ($admins as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'type' => 'success',
                    'title' => 'Lead Converted to Student',
                    'message' => "{$lead->full_name} has been converted from lead to student.",
                    'data' => [
                        'lead_id' => $lead->id,
                        'student_id' => $event->studentId,
                        'lead_name' => $lead->full_name,
                        'source' => $lead->source ?? 'Unknown',
                    ],
                    'action_url' => "/students/{$event->studentId}",
                    'read' => false,
                ]);
            }

            Log::info("Lead converted notifications sent", [
                'lead_id' => $lead->id,
                'student_id' => $event->studentId,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send lead converted notifications", [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

<?php

namespace App\Listeners;

use App\Events\PaymentDueSoon;
use App\Events\ExamScheduled;
use App\Events\ClassStartingSoon;
use App\Events\HomeworkDueSoon;
use App\Events\StudentBirthday;
use App\Services\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Send SMS Notification Listener
 *
 * Sends SMS notifications for various events.
 */
class SendSmsNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct(
        private SmsService $smsService
    ) {}

    /**
     * Handle the event.
     */
    public function handle($event): void
    {
        try {
            // Get recipient phone number
            $phone = $this->getRecipientPhone($event);

            if (!$phone) {
                Log::warning("No phone number found for SMS notification", [
                    'event' => get_class($event),
                ]);
                return;
            }

            // Check if user wants SMS notifications
            if (!$this->userWantsSmsNotifications($event)) {
                return;
            }

            // Get template and data
            $template = $this->getTemplate($event);
            $data = $this->getTemplateData($event);

            if (!$template) {
                Log::warning("No SMS template found for event", [
                    'event' => get_class($event),
                ]);
                return;
            }

            // Send SMS
            $result = $this->smsService->sendTemplate($phone, $template, $data);

            if ($result['success']) {
                event(new \App\Events\SmsSent(
                    $phone,
                    $this->smsService->renderTemplate($template, $data),
                    $result['sid'] ?? null,
                    $result['status'] ?? null,
                    $template,
                    $data
                ));

                Log::info("SMS notification sent", [
                    'event' => get_class($event),
                    'to' => $phone,
                ]);
            } else {
                event(new \App\Events\SmsFailed(
                    $phone,
                    $this->smsService->renderTemplate($template, $data),
                    $result['error'] ?? 'Unknown error',
                    $template,
                    $data
                ));

                Log::error("SMS notification failed", [
                    'event' => get_class($event),
                    'to' => $phone,
                    'error' => $result['error'] ?? 'Unknown error',
                ]);
            }
        } catch (\Exception $e) {
            Log::error("SMS notification listener error", [
                'event' => get_class($event),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get recipient phone number from event.
     */
    protected function getRecipientPhone($event): ?string
    {
        if ($event instanceof PaymentDueSoon) {
            return $event->student->phone;
        }

        if ($event instanceof ExamScheduled) {
            return $event->student->phone;
        }

        if ($event instanceof ClassStartingSoon) {
            return $event->student->phone;
        }

        if ($event instanceof HomeworkDueSoon) {
            return $event->student->phone;
        }

        if ($event instanceof StudentBirthday) {
            return $event->student->phone;
        }

        return null;
    }

    /**
     * Check if user wants SMS notifications.
     */
    protected function userWantsSmsNotifications($event): bool
    {
        $user = null;

        if (isset($event->student) && $event->student->user) {
            $user = $event->student->user;
        }

        if (!$user) {
            return true; // Default to sending if we can't check preferences
        }

        // Check user notification preferences
        $preferences = $user->notification_preferences ?? [];

        return in_array('sms', $preferences['channels'] ?? ['email', 'in_app']);
    }

    /**
     * Get SMS template for event.
     */
    protected function getTemplate($event): ?string
    {
        $templates = SmsService::getTemplates();

        if ($event instanceof PaymentDueSoon) {
            return $templates['payment_reminder'];
        }

        if ($event instanceof ExamScheduled) {
            return $templates['exam_reminder'];
        }

        if ($event instanceof ClassStartingSoon) {
            return $templates['class_reminder'];
        }

        if ($event instanceof HomeworkDueSoon) {
            return $templates['homework_reminder'];
        }

        if ($event instanceof StudentBirthday) {
            return $templates['birthday_wish'];
        }

        return null;
    }

    /**
     * Get template data for event.
     */
    protected function getTemplateData($event): array
    {
        if ($event instanceof PaymentDueSoon) {
            return [
                'amount' => number_format($event->payment->amount, 2),
                'invoice_number' => $event->payment->invoice_number,
                'due_date' => $event->payment->due_date->format('M j, Y'),
            ];
        }

        if ($event instanceof ExamScheduled) {
            return [
                'exam_type' => $event->exam->exam_type,
                'exam_date' => $event->exam->exam_date->format('M j, Y'),
                'time' => $event->exam->start_time,
            ];
        }

        if ($event instanceof ClassStartingSoon) {
            return [
                'class_name' => $event->class->name,
                'time' => $event->class->start_time,
            ];
        }

        if ($event instanceof HomeworkDueSoon) {
            return [
                'homework_title' => $event->homework->title,
                'due_date' => $event->homework->due_date->format('M j, Y'),
            ];
        }

        if ($event instanceof StudentBirthday) {
            return [
                'name' => $event->student->full_name,
            ];
        }

        return [];
    }
}

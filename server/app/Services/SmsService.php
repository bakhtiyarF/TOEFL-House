<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SMS Service
 *
 * Handles SMS notifications via Twilio or other SMS providers.
 */
class SmsService
{
    protected string $accountSid;
    protected string $authToken;
    protected string $fromNumber;
    protected string $apiUrl;

    public function __construct()
    {
        $this->accountSid = config('services.twilio.sid');
        $this->authToken = config('services.twilio.token');
        $this->fromNumber = config('services.twilio.from');
        $this->apiUrl = "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json";
    }

    /**
     * Send an SMS message.
     */
    public function send(string $to, string $message, ?array $options = []): array
    {
        // Validate phone number
        if (!$this->isValidPhoneNumber($to)) {
            Log::warning("Invalid phone number for SMS", ['to' => $to]);
            return [
                'success' => false,
                'error' => 'Invalid phone number',
            ];
        }

        // Truncate message if too long
        if (strlen($message) > 1600) {
            $message = substr($message, 0, 1597) . '...';
        }

        try {
            $response = Http::withBasicAuth($this->accountSid, $this->authToken)
                ->asForm()
                ->post($this->apiUrl, [
                    'From' => $this->fromNumber,
                    'To' => $this->formatPhoneNumber($to),
                    'Body' => $message,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                Log::info("SMS sent successfully", [
                    'to' => $to,
                    'sid' => $data['sid'] ?? null,
                ]);

                return [
                    'success' => true,
                    'sid' => $data['sid'] ?? null,
                    'status' => $data['status'] ?? 'queued',
                ];
            } else {
                $error = $response->json();

                Log::error("SMS sending failed", [
                    'to' => $to,
                    'status' => $response->status(),
                    'error' => $error['message'] ?? 'Unknown error',
                ]);

                return [
                    'success' => false,
                    'error' => $error['message'] ?? 'Failed to send SMS',
                    'status' => $response->status(),
                ];
            }
        } catch (\Exception $e) {
            Log::error("SMS sending exception", [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send SMS to multiple recipients.
     */
    public function sendBulk(array $recipients, string $message): array
    {
        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($recipients as $recipient) {
            $result = $this->send($recipient, $message);
            $results[] = array_merge(['recipient' => $recipient], $result);

            if ($result['success']) {
                $successCount++;
            } else {
                $failureCount++;
            }
        }

        return [
            'total' => count($recipients),
            'success' => $successCount,
            'failed' => $failureCount,
            'results' => $results,
        ];
    }

    /**
     * Send a templated SMS.
     */
    public function sendTemplate(string $to, string $template, array $data = []): array
    {
        $message = $this->renderTemplate($template, $data);
        return $this->send($to, $message);
    }

    /**
     * Render a message template.
     */
    protected function renderTemplate(string $template, array $data): string
    {
        $message = $template;

        foreach ($data as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }

        return $message;
    }

    /**
     * Validate phone number format.
     */
    protected function isValidPhoneNumber(string $phone): bool
    {
        // Remove all non-digit characters
        $cleaned = preg_replace('/\D/', '', $phone);

        // Check if it's a valid length (10-15 digits)
        return strlen($cleaned) >= 10 && strlen($cleaned) <= 15;
    }

    /**
     * Format phone number for SMS API.
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove all non-digit characters
        $cleaned = preg_replace('/\D/', '', $phone);

        // Add country code if not present (assume +1 for US)
        if (strlen($cleaned) === 10) {
            $cleaned = '1' . $cleaned;
        }

        return '+' . $cleaned;
    }

    /**
     * Check SMS delivery status.
     */
    public function checkStatus(string $sid): array
    {
        try {
            $response = Http::withBasicAuth($this->accountSid, $this->authToken)
                ->get("https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages/{$sid}.json");

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'status' => $data['status'] ?? 'unknown',
                    'error_code' => $data['error_code'] ?? null,
                    'error_message' => $data['error_message'] ?? null,
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to check status',
                    'status_code' => $response->status(),
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get SMS usage statistics.
     */
    public function getUsageStatistics(?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $endDate ?? now()->format('Y-m-d');

        try {
            $response = Http::withBasicAuth($this->accountSid, $this->authToken)
                ->get("https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Usage/Records.json", [
                    'StartDate' => $startDate,
                    'EndDate' => $endDate,
                    'Category' => 'sms',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $records = $data['usage_records'] ?? [];

                $totalMessages = 0;
                $totalCost = 0;

                foreach ($records as $record) {
                    $totalMessages += (int)($record['usage'] ?? 0);
                    $totalCost += (float)($record['price'] ?? 0);
                }

                return [
                    'success' => true,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'total_messages' => $totalMessages,
                    'total_cost' => round($totalCost, 4),
                    'currency' => $data['usage_records'][0]['price_unit'] ?? 'USD',
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to get usage statistics',
                    'status_code' => $response->status(),
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Common SMS templates.
     */
    public static function getTemplates(): array
    {
        return [
            'payment_reminder' => 'Reminder: Your payment of {amount} AFN for {invoice_number} is due on {due_date}. Please pay at your earliest convenience.',
            'payment_received' => 'Thank you! We received your payment of {amount} AFN for {invoice_number}.',
            'class_reminder' => 'Reminder: You have a {class_name} class tomorrow at {time}.',
            'exam_reminder' => 'Reminder: You have a {exam_type} exam on {exam_date} at {time}.',
            'homework_reminder' => 'Reminder: Homework "{homework_title}" is due on {due_date}.',
            'birthday_wish' => 'Happy Birthday, {name}! Wishing you a wonderful day from TOEFL House.',
            'enrollment_confirmation' => 'Welcome to TOEFL House! Your enrollment in {class_name} has been confirmed.',
            'grade_notification' => 'Your grade for {exam_title} has been posted: {score}/{max_score} ({percentage}%).',
            'attendance_alert' => 'Alert: You were marked absent for {class_name} on {date}. Please contact your teacher.',
            'certificate_issued' => 'Congratulations! Your certificate for {program_name} has been issued. Certificate #: {certificate_number}.',
        ];
    }
}

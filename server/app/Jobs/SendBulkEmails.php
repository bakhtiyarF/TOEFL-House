<?php

namespace App\Jobs;

use App\Mail\BulkNotificationMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Send Bulk Emails Job
 *
 * Sends bulk email notifications to multiple recipients.
 */
class SendBulkEmails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [10, 30, 60];

    public function __construct(
        private array $recipients,
        private string $subject,
        private string $template,
        private array $data,
        private string $requestedBy
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Sending bulk emails to " . count($this->recipients) . " recipients");

            $sent = 0;
            $failed = 0;

            foreach ($this->recipients as $recipient) {
                try {
                    Mail::to($recipient['email'])
                        ->send(new BulkNotificationMail(
                            $this->subject,
                            $this->template,
                            array_merge($this->data, ['recipient' => $recipient])
                        ));
                    $sent++;
                } catch (\Exception $e) {
                    $failed++;
                    Log::warning("Failed to send email to {$recipient['email']}: " . $e->getMessage());
                }
            }

            Log::info("Bulk email completed: {$sent} sent, {$failed} failed");

            // Optionally: Send notification to user who requested the bulk email
            // Notification::send($this->requestedBy, new BulkEmailCompletedNotification($sent, $failed));

        } catch (\Exception $e) {
            Log::error("Failed to send bulk emails: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Bulk email job failed permanently: " . $exception->getMessage());

        // Optionally: Send failure notification to user
        // Notification::send($this->requestedBy, new BulkEmailFailedNotification($exception));
    }
}

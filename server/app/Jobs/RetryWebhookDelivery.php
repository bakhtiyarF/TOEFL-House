<?php

namespace App\Jobs;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Services\WebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Retry Webhook Delivery Job
 *
 * Retries failed webhook deliveries with exponential backoff.
 */
class RetryWebhookDelivery implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [60, 300, 900];

    /**
     * Create a new job instance.
     */
    public function __construct(
        private string $deliveryId,
        private string $webhookId,
        private string $event,
        private array $payload
    ) {}

    /**
     * Execute the job.
     */
    public function handle(WebhookService $webhookService): void
    {
        try {
            $delivery = WebhookDelivery::find($this->deliveryId);
            $webhook = Webhook::find($this->webhookId);

            if (!$delivery || !$webhook) {
                Log::warning("Webhook delivery or webhook not found for retry", [
                    'delivery_id' => $this->deliveryId,
                    'webhook_id' => $this->webhookId,
                ]);
                return;
            }

            // Increment attempt count
            $delivery->incrementAttemptCount();

            // Attempt delivery
            $webhookService->dispatch($this->event, $this->payload, $webhook->branch_id);

            Log::info("Webhook delivery retry attempted", [
                'delivery_id' => $this->deliveryId,
                'webhook_id' => $this->webhookId,
                'event' => $this->event,
                'attempt' => $delivery->attempt_count,
            ]);
        } catch (\Exception $e) {
            Log::error("Webhook delivery retry failed", [
                'delivery_id' => $this->deliveryId,
                'webhook_id' => $this->webhookId,
                'event' => $this->event,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Webhook delivery retry job failed permanently", [
            'delivery_id' => $this->deliveryId,
            'webhook_id' => $this->webhookId,
            'event' => $this->event,
            'error' => $exception->getMessage(),
        ]);
    }
}

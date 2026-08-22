<?php

namespace App\Services;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Webhook Service
 *
 * Handles webhook delivery to external systems.
 */
class WebhookService
{
    /**
     * Dispatch a webhook event.
     */
    public function dispatch(string $event, array $payload, ?string $branchId = null): void
    {
        // Get all active webhooks subscribed to this event
        $webhooks = Webhook::active()
            ->forEvent($event)
            ->when($branchId, function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->get();

        foreach ($webhooks as $webhook) {
            $this->deliver($webhook, $event, $payload);
        }
    }

    /**
     * Deliver a webhook payload.
     */
    protected function deliver(Webhook $webhook, string $event, array $payload): void
    {
        // Create delivery record
        $delivery = WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'event' => $event,
            'payload' => $payload,
            'attempt_count' => 1,
        ]);

        try {
            // Prepare payload
            $jsonPayload = json_encode([
                'event' => $event,
                'timestamp' => now()->toIso8601String(),
                'data' => $payload,
            ]);

            // Generate signature
            $signature = $webhook->generateSignature($jsonPayload);

            // Send webhook
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event' => $event,
                    'X-Webhook-Delivery' => $delivery->id,
                ])
                ->withBody($jsonPayload, 'application/json')
                ->post($webhook->url);

            // Check response
            if ($response->successful()) {
                $delivery->markAsSuccessful(
                    $response->status(),
                    $response->body(),
                    $response->headers()
                );

                $webhook->markAsTriggered();

                Log::info("Webhook delivered successfully", [
                    'webhook_id' => $webhook->id,
                    'event' => $event,
                    'status' => $response->status(),
                ]);
            } else {
                $delivery->markAsFailed("HTTP {$response->status()}: {$response->body()}");

                Log::warning("Webhook delivery failed", [
                    'webhook_id' => $webhook->id,
                    'event' => $event,
                    'status' => $response->status(),
                    'error' => $response->body(),
                ]);

                // Retry if needed
                $this->retryDelivery($delivery, $webhook, $event, $payload);
            }
        } catch (\Exception $e) {
            $delivery->markAsFailed($e->getMessage());

            Log::error("Webhook delivery error", [
                'webhook_id' => $webhook->id,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);

            // Retry if needed
            $this->retryDelivery($delivery, $webhook, $event, $payload);
        }
    }

    /**
     * Retry a failed delivery.
     */
    protected function retryDelivery(WebhookDelivery $delivery, Webhook $webhook, string $event, array $payload): void
    {
        // Max 3 retry attempts
        if ($delivery->attempt_count >= 3) {
            Log::warning("Webhook delivery max retries reached", [
                'webhook_id' => $webhook->id,
                'event' => $event,
                'attempt_count' => $delivery->attempt_count,
            ]);
            return;
        }

        // Exponential backoff: 1min, 5min, 15min
        $delays = [60, 300, 900];
        $delay = $delays[$delivery->attempt_count - 1] ?? 900;

        // Dispatch retry job
        dispatch(new \App\Jobs\RetryWebhookDelivery($delivery->id, $webhook->id, $event, $payload))
            ->delay(now()->addSeconds($delay));
    }

    /**
     * Verify webhook signature.
     */
    public function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Get webhook statistics.
     */
    public function getStatistics(?string $branchId = null): array
    {
        $query = Webhook::query();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $totalWebhooks = $query->count();
        $activeWebhooks = $query->where('is_active', true)->count();

        $deliveryQuery = WebhookDelivery::query();

        if ($branchId) {
            $deliveryQuery->whereHas('webhook', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        $totalDeliveries = $deliveryQuery->count();
        $successfulDeliveries = $deliveryQuery->where('success', true)->count();
        $failedDeliveries = $deliveryQuery->where('success', false)->count();

        $successRate = $totalDeliveries > 0 ? ($successfulDeliveries / $totalDeliveries) * 100 : 0;

        return [
            'total_webhooks' => $totalWebhooks,
            'active_webhooks' => $activeWebhooks,
            'total_deliveries' => $totalDeliveries,
            'successful_deliveries' => $successfulDeliveries,
            'failed_deliveries' => $failedDeliveries,
            'success_rate' => round($successRate, 2),
        ];
    }
}

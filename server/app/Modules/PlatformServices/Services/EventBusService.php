<?php

namespace App\Modules\PlatformServices\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * Event Bus Service
 *
 * Implements the publish/dispatch pattern from 08_PLATFORM_SERVICES_MODULE.md §6.
 * Events are written to the domain_events table; matching subscriptions determine
 * what happens next; each handler runs at most once (event_handler_log uniqueness).
 *
 * Confirmed event → effect mappings (02 §9):
 * - student.registered → notification
 * - payment.received → notification
 * - expense.requested → workflow instance creation
 */
class EventBusService
{
    /**
     * Publish a domain event and dispatch to all matching handlers
     */
    public function publish(
        string $type,
        string $aggregateType,
        string $aggregateId,
        array $payload,
        string $branchId,
        ?string $operatorId = null,
        ?string $correlationId = null,
        ?string $causationId = null,
    ): string {
        $eventId = Str::uuid()->toString();

        // Write to event store (append-only)
        DB::table('domain_events')->insert([
            'id' => $eventId,
            'type' => $type,
            'aggregate_type' => $aggregateType,
            'aggregate_id' => $aggregateId,
            'payload' => json_encode($payload),
            'occurred_at' => now(),
            'operator_id' => $operatorId,
            'branch_id' => $branchId,
            'correlation_id' => $correlationId,
            'causation_id' => $causationId,
            'schema_version' => 1,
            'published' => true,
        ]);

        // Dispatch to matching subscriptions
        $this->dispatch($eventId, $type, $payload, $branchId);

        return $eventId;
    }

    /**
     * Dispatch event to all matching handlers
     */
    private function dispatch(string $eventId, string $eventType, array $payload, string $branchId): void
    {
        $subscriptions = DB::table('event_subscriptions')
            ->where('event_type', $eventType)
            ->where('is_active', true)
            ->get();

        foreach ($subscriptions as $subscription) {
            $startTime = microtime(true);

            try {
                // Check idempotency: handler runs at most once per event
                $alreadyHandled = DB::table('event_handler_log')
                    ->where('event_id', $eventId)
                    ->where('handler', $subscription->handler)
                    ->exists();

                if ($alreadyHandled) {
                    continue;
                }

                $this->executeHandler($subscription, $eventId, $payload, $branchId);

                // Log successful execution
                DB::table('event_handler_log')->insert([
                    'id' => Str::uuid()->toString(),
                    'event_id' => $eventId,
                    'handler' => $subscription->handler,
                    'success' => true,
                    'duration_ms' => (int)((microtime(true) - $startTime) * 1000),
                    'executed_at' => now(),
                ]);
            } catch (\Throwable $e) {
                Log::error('Event handler failed', [
                    'event_id' => $eventId,
                    'handler' => $subscription->handler,
                    'error' => $e->getMessage(),
                ]);

                // Log failure (still recorded to prevent infinite retries)
                DB::table('event_handler_log')->insert([
                    'id' => Str::uuid()->toString(),
                    'event_id' => $eventId,
                    'handler' => $subscription->handler,
                    'success' => false,
                    'duration_ms' => (int)((microtime(true) - $startTime) * 1000),
                    'error' => $e->getMessage(),
                    'executed_at' => now(),
                ]);
            }
        }
    }

    /**
     * Execute a specific handler for an event
     */
    private function executeHandler(object $subscription, string $eventId, array $payload, string $branchId): void
    {
        match ($subscription->handler) {
            'notification' => $this->handleNotification($subscription, $payload, $branchId),
            'workflow' => $this->handleWorkflow($subscription, $eventId, $payload, $branchId),
            'automation' => $this->handleAutomation($subscription, $payload, $branchId),
            'webhook' => $this->handleWebhook($subscription, $payload),
            default => null,
        };
    }

    /**
     * Create notification from event (08 §6)
     */
    private function handleNotification(object $subscription, array $payload, string $branchId): void
    {
        $config = json_decode($subscription->config ?? '{}', true);

        DB::table('notifications')->insert([
            'id' => Str::uuid()->toString(),
            'title' => $config['title'] ?? 'System Notification',
            'message' => $config['message_template'] ?? json_encode($payload),
            'date' => now()->toDateString(),
            'read' => false,
            'type' => $config['type'] ?? 'info',
            'user_id' => $config['target_user_id'] ?? null,
            'branch_id' => $branchId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Create workflow instance from event (08 §6: expense.requested → workflow)
     */
    private function handleWorkflow(object $subscription, string $eventId, array $payload, string $branchId): void
    {
        $config = json_decode($subscription->config ?? '{}', true);
        $definitionId = $config['definition_id'] ?? null;

        if (!$definitionId) {
            return;
        }

        $definition = DB::table('workflow_definitions')
            ->where('id', $definitionId)
            ->where('is_active', true)
            ->first();

        if (!$definition) {
            return;
        }

        DB::table('workflow_instances')->insert([
            'id' => Str::uuid()->toString(),
            'definition_id' => $definitionId,
            'entity_type' => $payload['entity_type'] ?? 'unknown',
            'entity_id' => $payload['entity_id'] ?? $eventId,
            'current_step' => 0,
            'status' => 'pending',
            'branch_id' => $branchId,
            'initiated_by' => $payload['operator_id'] ?? null,
            'started_at' => now(),
            'payload' => json_encode($payload),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Execute automation side-effects
     */
    private function handleAutomation(object $subscription, array $payload, string $branchId): void
    {
        $config = json_decode($subscription->config ?? '{}', true);
        // Automation execution — delegated to specific automation logic
        Log::info('Automation triggered', ['config' => $config, 'payload' => $payload]);
    }

    /**
     * Send webhook (future implementation)
     */
    private function handleWebhook(object $subscription, array $payload): void
    {
        $config = json_decode($subscription->config ?? '{}', true);
        // Webhook POST to configured URL — future implementation
        Log::info('Webhook triggered', ['url' => $config['url'] ?? null]);
    }

    /**
     * Replay an event — re-dispatches to handlers that haven't run yet
     * Safe due to event_handler_log uniqueness constraint
     */
    public function replay(string $eventId): void
    {
        $event = DB::table('domain_events')->where('id', $eventId)->first();
        if (!$event) {
            throw new \RuntimeException('Event not found');
        }

        $payload = json_decode($event->payload ?? '{}', true);
        $this->dispatch($eventId, $event->type, $payload, $event->branch_id);
    }
}

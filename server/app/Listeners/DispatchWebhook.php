<?php

namespace App\Listeners;

use App\Services\WebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Dispatch Webhook Listener
 *
 * Dispatches webhooks when domain events occur.
 */
class DispatchWebhook implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct(
        private WebhookService $webhookService
    ) {}

    /**
     * Handle the event.
     */
    public function handle($event): void
    {
        try {
            // Get event name
            $eventName = $this->getEventName($event);

            if (!$eventName) {
                return;
            }

            // Get payload
            $payload = $this->getPayload($event);

            // Get branch ID if available
            $branchId = $this->getBranchId($event);

            // Dispatch webhook
            $this->webhookService->dispatch($eventName, $payload, $branchId);

            Log::info("Webhook dispatched for event", [
                'event' => $eventName,
                'branch_id' => $branchId,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to dispatch webhook", [
                'event' => get_class($event),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the event name.
     */
    protected function getEventName($event): ?string
    {
        $eventMap = [
            \App\Events\StudentCreated::class => 'student.created',
            \App\Events\StudentUpdated::class => 'student.updated',
            \App\Events\StudentDeleted::class => 'student.deleted',
            \App\Events\TeacherCreated::class => 'teacher.created',
            \App\Events\TeacherUpdated::class => 'teacher.updated',
            \App\Events\ClassCreated::class => 'class.created',
            \App\Events\ClassUpdated::class => 'class.updated',
            \App\Events\EnrollmentCreated::class => 'enrollment.created',
            \App\Events\EnrollmentUpdated::class => 'enrollment.updated',
            \App\Events\AttendanceRecorded::class => 'attendance.recorded',
            \App\Events\GradePosted::class => 'grade.posted',
            \App\Events\PaymentReceived::class => 'payment.received',
            \App\Events\InvoiceCreated::class => 'invoice.created',
            \App\Events\InvoicePaid::class => 'invoice.paid',
            \App\Events\DonationReceived::class => 'donation.received',
            \App\Events\CertificateIssued::class => 'certificate.issued',
            \App\Events\ExamScheduled::class => 'exam.scheduled',
            \App\Events\HomeworkSubmitted::class => 'homework.submitted',
            \App\Events\BranchCreated::class => 'branch.created',
            \App\Events\BranchUpdated::class => 'branch.updated',
        ];

        return $eventMap[get_class($event)] ?? null;
    }

    /**
     * Get the payload from the event.
     */
    protected function getPayload($event): array
    {
        // Get public properties from the event
        $reflection = new \ReflectionClass($event);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

        $payload = [];

        foreach ($properties as $property) {
            $name = $property->getName();
            $value = $property->getValue($event);

            // Convert models to arrays
            if ($value instanceof \Illuminate\Database\Eloquent\Model) {
                $payload[$name] = $value->toArray();
            } else {
                $payload[$name] = $value;
            }
        }

        return $payload;
    }

    /**
     * Get the branch ID from the event.
     */
    protected function getBranchId($event): ?string
    {
        // Try to get branch ID from event properties
        $reflection = new \ReflectionClass($event);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            $value = $property->getValue($event);

            // Check if it's a model with branch_id
            if ($value instanceof \Illuminate\Database\Eloquent\Model) {
                if (isset($value->branch_id)) {
                    return $value->branch_id;
                }

                // Check if model has branch relationship
                if (method_exists($value, 'branch') && $value->branch) {
                    return $value->branch->id;
                }
            }
        }

        return null;
    }
}

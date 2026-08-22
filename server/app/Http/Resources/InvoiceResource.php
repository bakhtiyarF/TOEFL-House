<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Invoice Resource
 *
 * Transforms invoice model for API responses.
 */
class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
            'status' => $this->status,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'notes' => $this->notes,
            
            // Relationships
            'student' => new StudentResource($this->whenLoaded('student')),
            'enrollment' => new EnrollmentResource($this->whenLoaded('enrollment')),
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'issuer' => new UserResource($this->whenLoaded('issuer')),
            
            // Computed attributes
            'amount_paid' => $this->amount_paid,
            'amount_due' => $this->amount_due,
            'is_paid' => $this->isPaid(),
            'is_overdue' => $this->isOverdue(),
            'days_overdue' => $this->days_overdue,
            
            // Metadata
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

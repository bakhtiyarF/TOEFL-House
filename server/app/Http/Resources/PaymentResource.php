<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Payment Resource
 *
 * Transforms payment model for API responses.
 */
class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'receipt_number' => $this->receipt_number,
            'amount' => $this->amount,
            'payment_method' => $this->payment_method,
            'payment_method_label' => $this->payment_method_label,
            'category' => $this->category,
            'category_label' => $this->category_label,
            'status' => $this->status,
            'date' => $this->date?->format('Y-m-d'),
            'notes' => $this->notes,
            'semester' => $this->semester,
            
            // Relationships
            'student' => new StudentResource($this->whenLoaded('student')),
            'invoice' => new InvoiceResource($this->whenLoaded('invoice')),
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'financial_transaction' => new FinancialTransactionResource($this->whenLoaded('financialTransaction')),
            
            // Computed attributes
            'is_refundable' => $this->isRefundable(),
            
            // Metadata
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

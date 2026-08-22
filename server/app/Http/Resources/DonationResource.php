<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Donation Resource
 *
 * Transforms donation model for API responses.
 */
class DonationResource extends JsonResource
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
            'currency' => $this->currency,
            'formatted_amount' => $this->formatted_amount,
            'donation_date' => $this->donation_date?->format('Y-m-d'),
            'payment_method' => $this->payment_method,
            'is_recurring' => $this->is_recurring,
            'recurrence_frequency' => $this->recurrence_frequency,
            'notes' => $this->notes,
            'status' => $this->status,
            
            // Relationships
            'donor' => new DonorResource($this->whenLoaded('donor')),
            'campaign' => new CampaignResource($this->whenLoaded('campaign')),
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'scholarships' => ScholarshipResource::collection($this->whenLoaded('scholarships')),
            'financial_transactions' => FinancialTransactionResource::collection($this->whenLoaded('financialTransactions')),
            
            // Computed attributes
            'days_ago' => $this->days_ago,
            
            // Metadata
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

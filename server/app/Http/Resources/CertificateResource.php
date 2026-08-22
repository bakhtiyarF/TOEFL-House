<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Certificate Resource
 *
 * Transforms certificate model for API responses.
 */
class CertificateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'certificate_number' => $this->certificate_number,
            'certificate_type' => $this->certificate_type,
            'issue_date' => $this->issue_date?->format('Y-m-d'),
            'expiry_date' => $this->expiry_date?->format('Y-m-d'),
            'status' => $this->status,
            'qr_code' => $this->qr_code,
            'verification_url' => $this->verification_url,
            
            // Relationships
            'student' => new StudentResource($this->whenLoaded('student')),
            'program' => new ProgramResource($this->whenLoaded('program')),
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'issuer' => new UserResource($this->whenLoaded('issuer')),
            
            // Computed attributes
            'is_valid' => $this->isValid(),
            'is_expired' => $this->isExpired(),
            'days_until_expiry' => $this->days_until_expiry,
            
            // Metadata
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

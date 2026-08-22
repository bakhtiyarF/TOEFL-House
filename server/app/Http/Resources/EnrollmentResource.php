<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Enrollment Resource
 *
 * Transforms enrollment model for API responses.
 */
class EnrollmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'enrollment_type' => $this->enrollment_type,
            'status' => $this->status,
            'started_at' => $this->started_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'discount_percent' => $this->discount_percent,
            'scholarship_percent' => $this->scholarship_percent,
            'skills_focus' => $this->skills_focus,
            
            // Fee snapshot (copy-on-write)
            'fee_snapshot' => $this->when(
                $request->user()?->can('viewFinancial', $this->student),
                $this->fee_snapshot_json
            ),
            
            // Relationships
            'student' => new StudentResource($this->whenLoaded('student')),
            'program' => new ProgramResource($this->whenLoaded('program')),
            'program_version' => new ProgramVersionResource($this->whenLoaded('programVersion')),
            'class' => new ClassResource($this->whenLoaded('academicClass')),
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'journey_events' => JourneyEventResource::collection($this->whenLoaded('journeyEvents')),
            
            // Computed attributes
            'gross_tuition' => $this->when(
                $request->user()?->can('viewFinancial', $this->student),
                $this->gross_tuition
            ),
            'net_tuition' => $this->when(
                $request->user()?->can('viewFinancial', $this->student),
                $this->net_tuition
            ),
            'total_paid' => $this->when(
                $request->user()?->can('viewFinancial', $this->student),
                $this->total_paid
            ),
            'remaining_balance' => $this->when(
                $request->user()?->can('viewFinancial', $this->student),
                $this->remaining_balance
            ),
            'is_fully_paid' => $this->when(
                $request->user()?->can('viewFinancial', $this->student),
                $this->isFullyPaid()
            ),
            'duration_in_days' => $this->duration_in_days,
            
            // Metadata
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

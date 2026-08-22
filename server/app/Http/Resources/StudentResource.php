<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Student Resource
 *
 * Transforms student model for API responses.
 */
class StudentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'student_code' => $this->student_code,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'gender' => $this->gender,
            'father_name' => $this->father_name,
            'address_region' => $this->address_region,
            'tazkira_no' => $this->when($request->user()?->can('view', $this), $this->tazkira_no),
            'whatsapp' => $this->whatsapp,
            'dob' => $this->dob?->format('Y-m-d'),
            'school_or_university' => $this->school_or_university,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'placement_score' => $this->when(
                $request->user()?->can('viewPlacement', $this),
                $this->placement_score
            ),
            'status' => $this->status,
            'registration_date' => $this->registration_date?->format('Y-m-d'),
            'discount_percent' => $this->discount_percent,
            
            // Relationships
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'current_enrollment' => new EnrollmentResource($this->whenLoaded('currentEnrollment')),
            'active_enrollments' => EnrollmentResource::collection($this->whenLoaded('activeEnrollments')),
            
            // Computed attributes
            'total_paid' => $this->when(
                $request->user()?->can('viewFinancial', $this),
                $this->total_paid
            ),
            'total_due' => $this->when(
                $request->user()?->can('viewFinancial', $this),
                $this->total_due
            ),
            'is_fully_paid' => $this->when(
                $request->user()?->can('viewFinancial', $this),
                $this->isFullyPaid()
            ),
            'attendance_rate' => $this->attendance_rate,
            
            // Metadata
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Visitor Resource
 *
 * Transforms visitor/lead model for API responses.
 */
class VisitorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'serial_no' => $this->serial_no,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'gender' => $this->gender,
            'father_name' => $this->father_name,
            'address_region' => $this->address_region,
            'tazkira_no' => $this->when(
                $request->user()?->can('view', $this->resource),
                $this->tazkira_no
            ),
            'whatsapp' => $this->whatsapp,
            'dob' => $this->dob?->format('Y-m-d'),
            'school_or_university' => $this->school_or_university,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'source' => $this->source,
            'stage' => $this->stage,
            'status' => $this->status,
            'interested_program' => $this->interested_program,
            'placement_score' => $this->when(
                $request->user()?->can('viewPlacement', $this->resource),
                $this->placement_score
            ),
            'placement_test_date' => $this->placement_test_date?->format('Y-m-d'),
            'follow_up_date' => $this->follow_up_date?->format('Y-m-d'),
            'follow_up_notes' => $this->follow_up_notes,
            'visit_date' => $this->visit_date?->format('Y-m-d'),
            'converted_at' => $this->converted_at?->toIso8601String(),
            
            // Relationships
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'campaign' => new CampaignResource($this->whenLoaded('campaign')),
            'assigned_user' => new UserResource($this->whenLoaded('assignedUser')),
            'converted_student' => new StudentResource($this->whenLoaded('convertedStudent')),
            'follow_ups' => VisitorFollowUpResource::collection($this->whenLoaded('followUps')),
            'latest_follow_up' => new VisitorFollowUpResource($this->whenLoaded('latestFollowUp')),
            
            // Computed attributes
            'is_converted' => $this->isConverted(),
            'placement_score_value' => $this->placement_score_value,
            'days_since_last_follow_up' => $this->days_since_last_follow_up,
            'days_in_pipeline' => $this->days_in_pipeline,
            
            // Metadata
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Program Resource
 *
 * Transforms program model for API responses.
 */
class ProgramResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'duration_months' => $this->duration_months,
            'total_credits' => $this->total_credits,
            'status' => $this->status,
            'min_gpa_requirement' => $this->min_gpa_requirement,
            
            // Relationships
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'versions' => ProgramVersionResource::collection($this->whenLoaded('versions')),
            'active_version' => new ProgramVersionResource($this->whenLoaded('activeVersion')),
            'levels' => LevelResource::collection($this->whenLoaded('levels')),
            'classes' => ClassResource::collection($this->whenLoaded('classes')),
            
            // Computed attributes
            'is_active' => $this->status === 'active',
            'active_enrollment_count' => $this->active_enrollment_count,
            'total_graduates' => $this->total_graduates,
            'completion_rate' => $this->completion_rate,
            
            // Metadata
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

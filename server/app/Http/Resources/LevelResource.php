<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Level Resource
 *
 * Transforms level model for API responses.
 */
class LevelResource extends JsonResource
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
            'order' => $this->order,
            'duration_months' => $this->duration_months,
            'min_passing_score' => $this->min_passing_score,
            
            // Relationships
            'program' => new ProgramResource($this->whenLoaded('program')),
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'subjects' => SubjectResource::collection($this->whenLoaded('subjects')),
            'classes' => ClassResource::collection($this->whenLoaded('classes')),
            
            // Computed attributes
            'active_enrollment_count' => $this->active_enrollment_count,
            'total_students' => $this->total_students,
            'completion_rate' => $this->completion_rate,
            'average_score' => $this->average_score,
            
            // Metadata
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

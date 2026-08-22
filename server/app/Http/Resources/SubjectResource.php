<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Subject Resource
 *
 * Transforms subject model for API responses.
 */
class SubjectResource extends JsonResource
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
            'credits' => $this->credits,
            'hours_per_week' => $this->hours_per_week,
            
            // Relationships
            'level' => new LevelResource($this->whenLoaded('level')),
            'program' => new ProgramResource($this->whenLoaded('program')),
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'teachers' => TeacherResource::collection($this->whenLoaded('teachers')),
            'classes' => ClassResource::collection($this->whenLoaded('classes')),
            
            // Computed attributes
            'active_enrollment_count' => $this->active_enrollment_count,
            'total_students' => $this->total_students,
            'average_score' => $this->average_score,
            
            // Metadata
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

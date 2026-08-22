<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Exam Resource
 *
 * Transforms exam model for API responses.
 */
class ExamResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'exam_type' => $this->exam_type,
            'exam_date' => $this->exam_date?->format('Y-m-d'),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'duration_minutes' => $this->duration_minutes,
            'total_marks' => $this->total_marks,
            'passing_marks' => $this->passing_marks,
            'status' => $this->status,
            'instructions' => $this->instructions,
            
            // Relationships
            'class' => new ClassResource($this->whenLoaded('class')),
            'program' => new ProgramResource($this->whenLoaded('program')),
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'grades' => GradeResource::collection($this->whenLoaded('grades')),
            'invigilators' => UserResource::collection($this->whenLoaded('invigilators')),
            
            // Computed attributes
            'is_scheduled' => $this->status === 'scheduled',
            'is_completed' => $this->status === 'completed',
            'is_ongoing' => $this->status === 'ongoing',
            'pass_percentage' => $this->pass_percentage,
            'average_score' => $this->average_score,
            'total_students' => $this->grades_count ?? $this->grades()->count(),
            
            // Metadata
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

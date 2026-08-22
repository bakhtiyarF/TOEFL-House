<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Grade Resource
 *
 * Transforms grade model for API responses.
 */
class GradeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'score' => $this->score,
            'max_score' => $this->max_score,
            'percentage' => $this->percentage,
            'grade_letter' => $this->grade_letter,
            'is_passed' => $this->is_passed,
            'is_published' => $this->is_published,
            'feedback' => $this->feedback,
            'graded_at' => $this->graded_at?->toIso8601String(),
            'published_at' => $this->published_at?->toIso8601String(),
            
            // Relationships
            'student' => new StudentResource($this->whenLoaded('student')),
            'exam' => new ExamResource($this->whenLoaded('exam')),
            'class' => new ClassResource($this->whenLoaded('class')),
            'grader' => new UserResource($this->whenLoaded('grader')),
            
            // Computed attributes
            'formatted_percentage' => number_format($this->percentage, 2) . '%',
            'can_be_updated' => !$this->is_published,
            
            // Metadata
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

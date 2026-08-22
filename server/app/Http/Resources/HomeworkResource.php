<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Homework Resource
 *
 * Transforms homework model for API responses.
 */
class HomeworkResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'due_date' => $this->due_date?->toIso8601String(),
            'max_score' => $this->max_score,
            'attachments' => $this->attachments,
            
            // Relationships
            'session' => new SessionResource($this->whenLoaded('session')),
            'class' => new ClassResource($this->whenLoaded('class')),
            'submissions' => HomeworkSubmissionResource::collection($this->whenLoaded('submissions')),
            
            // Computed attributes
            'is_overdue' => $this->isOverdue(),
            'submission_count' => $this->submissions_count ?? $this->submissions()->count(),
            'average_score' => $this->average_score,
            'submission_rate' => $this->submission_rate,
            
            // Metadata
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

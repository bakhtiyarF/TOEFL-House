<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Session Resource
 *
 * Transforms session model for API responses.
 */
class SessionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'session_date' => $this->session_date?->format('Y-m-d'),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'topic' => $this->topic,
            'status' => $this->status,
            'notes' => $this->notes,
            
            // Relationships
            'class' => new ClassResource($this->whenLoaded('class')),
            'teacher' => new TeacherResource($this->whenLoaded('teacher')),
            'rosters' => RosterResource::collection($this->whenLoaded('rosters')),
            'homework' => HomeworkResource::collection($this->whenLoaded('homework')),
            
            // Computed attributes
            'attendance_rate' => $this->when(
                $this->relationLoaded('rosters'),
                $this->attendance_rate
            ),
            'present_count' => $this->when(
                $this->relationLoaded('rosters'),
                $this->present_count
            ),
            'absent_count' => $this->when(
                $this->relationLoaded('rosters'),
                $this->absent_count
            ),
            'duration_minutes' => $this->duration_minutes,
            
            // Metadata
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

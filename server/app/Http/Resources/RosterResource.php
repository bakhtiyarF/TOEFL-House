<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Roster Resource
 *
 * Transforms roster/attendance model for API responses.
 */
class RosterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'attendance_status' => $this->attendance_status,
            'marked_at' => $this->marked_at?->toIso8601String(),
            'notes' => $this->notes,
            
            // Relationships
            'student' => new StudentResource($this->whenLoaded('student')),
            'session' => new SessionResource($this->whenLoaded('session')),
            
            // Computed attributes
            'is_present' => $this->attendance_status === 'present',
            'is_absent' => $this->attendance_status === 'absent',
            'is_late' => $this->attendance_status === 'late',
            
            // Metadata
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

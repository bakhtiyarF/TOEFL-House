<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class Resource
 *
 * Transforms academic class model for API responses.
 */
class ClassResource extends JsonResource
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
            'level' => $this->level,
            'capacity' => $this->capacity,
            'min_viable_size' => $this->min_viable_size,
            'schedule_time' => $this->schedule_time,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'activation_date' => $this->activation_date?->format('Y-m-d'),
            'status' => $this->status,
            'fee' => $this->fee,
            'gender_policy' => $this->gender_policy,
            
            // Relationships
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'teacher' => new TeacherResource($this->whenLoaded('teacher')),
            'program' => new ProgramResource($this->whenLoaded('program')),
            'level_detail' => new LevelResource($this->whenLoaded('levelRelation')),
            'sessions' => SessionResource::collection($this->whenLoaded('sessions')),
            'active_semesters' => StudentSemesterResource::collection($this->whenLoaded('activeSemesters')),
            
            // Computed attributes
            'enrolled_count' => $this->enrolled_count,
            'fill_percent' => $this->fill_percent,
            'is_below_minimum_size' => $this->isBelowMinimumSize(),
            'is_full' => $this->isFull(),
            'attendance_rate' => $this->attendance_rate,
            
            // Metadata
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

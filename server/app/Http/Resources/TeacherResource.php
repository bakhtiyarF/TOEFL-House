<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Teacher Resource
 *
 * Transforms teacher model for API responses.
 */
class TeacherResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'gender' => $this->gender,
            'specialization' => $this->specialization,
            'qualification' => $this->qualification,
            'experience_years' => $this->experience_years,
            'salary_type' => $this->salary_type,
            'base_salary' => $this->when(
                $request->user()?->can('viewSalary', $this->resource),
                $this->base_salary
            ),
            'status' => $this->status,
            'join_date' => $this->join_date?->format('Y-m-d'),
            
            // Relationships
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'user' => new UserResource($this->whenLoaded('user')),
            'active_classes' => ClassResource::collection($this->whenLoaded('activeClasses')),
            'evaluations' => EvaluationResource::collection($this->whenLoaded('evaluations')),
            
            // Computed attributes
            'active_class_count' => $this->active_class_count,
            'total_students' => $this->when(
                $request->user()?->can('view', $this->resource),
                $this->total_students
            ),
            'average_rating' => $this->average_rating,
            
            // Metadata
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

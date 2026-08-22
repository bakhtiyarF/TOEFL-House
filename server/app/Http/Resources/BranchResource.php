<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Branch Resource
 *
 * Transforms branch model for API responses.
 */
class BranchResource extends JsonResource
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
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'status' => $this->status,
            'timezone' => $this->timezone,
            'currency' => $this->currency,
            
            // Relationships
            'organization' => new OrganizationResource($this->whenLoaded('organization')),
            'users' => UserResource::collection($this->whenLoaded('users')),
            'students' => StudentResource::collection($this->whenLoaded('students')),
            'teachers' => TeacherResource::collection($this->whenLoaded('teachers')),
            'classes' => ClassResource::collection($this->whenLoaded('classes')),
            
            // Computed attributes
            'is_active' => $this->status === 'active',
            'active_student_count' => $this->active_student_count,
            'active_teacher_count' => $this->active_teacher_count,
            'active_class_count' => $this->active_class_count,
            'total_revenue_this_month' => $this->when(
                $request->user()?->can('viewReports', $this->resource),
                $this->total_revenue_this_month
            ),
            
            // Metadata
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

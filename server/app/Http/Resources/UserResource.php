<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * User Resource
 *
 * Transforms user model for API responses.
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'status' => $this->status,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            
            // Relationships
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
            'teacher' => new TeacherResource($this->whenLoaded('teacher')),
            'student' => new StudentResource($this->whenLoaded('student')),
            
            // Computed attributes
            'is_active' => $this->status === 'active',
            'is_email_verified' => $this->email_verified_at !== null,
            'has_two_factor' => $this->two_factor_enabled ?? false,
            'role_names' => $this->when(
                $this->relationLoaded('roles'),
                $this->roles->pluck('name')->toArray()
            ),
            
            // Metadata
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

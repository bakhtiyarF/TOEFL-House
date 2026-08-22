<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Role Resource
 *
 * Transforms role model for API responses.
 */
class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'is_system' => $this->is_system,
            'is_active' => $this->is_active,
            
            // Relationships
            'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
            'users' => UserResource::collection($this->whenLoaded('users')),
            
            // Computed attributes
            'permissions_count' => $this->permissions_count ?? $this->permissions()->count(),
            'users_count' => $this->users_count ?? $this->users()->count(),
            'can_be_edited' => !$this->is_system,
            'can_be_deleted' => !$this->is_system && ($this->users_count ?? $this->users()->count()) === 0,
            
            // Metadata
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

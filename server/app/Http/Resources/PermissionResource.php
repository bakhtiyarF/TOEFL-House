<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Permission Resource
 *
 * Transforms permission model for API responses.
 */
class PermissionResource extends JsonResource
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
            'group' => $this->group,
            'is_system' => $this->is_system,
            
            // Relationships
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            
            // Computed attributes
            'roles_count' => $this->roles_count ?? $this->roles()->count(),
            'can_be_edited' => !$this->is_system,
            'can_be_deleted' => !$this->is_system && ($this->roles_count ?? $this->roles()->count()) === 0,
            
            // Metadata
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Organization Resource
 *
 * Transforms organization model for API responses.
 */
class OrganizationResource extends JsonResource
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
            'website' => $this->website,
            'status' => $this->status,
            'timezone' => $this->timezone,
            'currency' => $this->currency,
            'logo_url' => $this->logo_url,
            
            // Relationships
            'branches' => BranchResource::collection($this->whenLoaded('branches')),
            'users' => UserResource::collection($this->whenLoaded('users')),
            
            // Computed attributes
            'is_active' => $this->status === 'active',
            'branches_count' => $this->branches_count ?? $this->branches()->count(),
            'users_count' => $this->users_count ?? $this->users()->count(),
            'total_students' => $this->when(
                $request->user()?->can('viewReports', $this->resource),
                $this->total_students
            ),
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

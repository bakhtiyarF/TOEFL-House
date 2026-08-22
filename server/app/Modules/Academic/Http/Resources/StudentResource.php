<?php

namespace App\Modules\Academic\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_code' => $this->student_code,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'gender' => $this->gender,
            'father_name' => $this->father_name,
            'tazkira_no' => $this->tazkira_no,
            'status' => $this->status,
            'discount_percent' => (float) $this->discount_percent,
            'registration_date' => $this->registration_date?->toDateString(),
            'branch' => $this->whenLoaded('branch'),
            'attendance_rate' => $this->attendance_rate,
            'total_due' => $this->total_due,
            'created_at' => $this->created_at,
        ];
    }
}

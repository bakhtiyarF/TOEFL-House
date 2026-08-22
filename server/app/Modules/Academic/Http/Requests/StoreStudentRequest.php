<?php

namespace App\Modules\Academic\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy checked in controller
    }

    public function rules(): array
    {
        return [
            'full_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'gender' => 'nullable|in:male,female',
            'father_name' => 'nullable|string|max:255',
            'address_region' => 'nullable|string|max:255',
            'tazkira_no' => 'nullable|string|max:50',
            'whatsapp' => 'nullable|string|max:50',
            'dob' => 'nullable|date',
            'school_or_university' => 'nullable|string|max:255',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:50',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'branch_id' => 'required|uuid|exists:branches,id',
        ];
    }
}

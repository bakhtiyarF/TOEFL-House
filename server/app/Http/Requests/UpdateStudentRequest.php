<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Student Request
 *
 * Validates student update data.
 */
class UpdateStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $student = $this->route('student');
        return $this->user()?->can('update', $student) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $studentId = $this->route('student')?->id;
        
        return [
            'full_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('students')->ignore($studentId)],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'father_name' => ['nullable', 'string', 'max:255'],
            'address_region' => ['nullable', 'string', 'max:255'],
            'tazkira_no' => ['nullable', 'string', 'max:50', Rule::unique('students')->ignore($studentId)],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'dob' => ['nullable', 'date', 'before:today'],
            'school_or_university' => ['nullable', 'string', 'max:255'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'placement_score' => ['nullable', 'array'],
            'placement_score.score' => ['required_with:placement_score', 'numeric', 'min:0', 'max:100'],
            'placement_score.feePaid' => ['nullable', 'boolean'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'graduated', 'suspended'])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'A student with this email already exists.',
            'tazkira_no.unique' => 'A student with this Tazkira number already exists.',
            'dob.before' => 'Date of birth must be in the past.',
            'placement_score.score.min' => 'Placement score must be at least 0.',
            'placement_score.score.max' => 'Placement score cannot exceed 100.',
            'discount_percent.min' => 'Discount percent must be at least 0.',
            'discount_percent.max' => 'Discount percent cannot exceed 100.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Trim string fields
        $this->merge(array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $this->all()));
    }
}

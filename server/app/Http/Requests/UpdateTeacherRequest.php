<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Teacher Request
 *
 * Validates teacher update data.
 */
class UpdateTeacherRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $teacher = $this->route('teacher');
        return $this->user()?->can('update', $teacher) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $teacherId = $this->route('teacher')?->id;
        
        return [
            'full_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('teachers')->ignore($teacherId)],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'specialization' => ['nullable', 'string', 'max:255'],
            'qualification' => ['nullable', 'string', 'max:255'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:50'],
            'salary_type' => ['sometimes', Rule::in(['fixed', 'per_hour', 'per_class', 'commission'])],
            'base_salary' => ['required_if:salary_type,fixed', 'nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'hourly_rate' => ['required_if:salary_type,per_hour', 'nullable', 'numeric', 'min:0', 'max:99999.99'],
            'per_class_rate' => ['required_if:salary_type,per_class', 'nullable', 'numeric', 'min:0', 'max:99999.99'],
            'commission_percent' => ['required_if:salary_type,commission', 'nullable', 'numeric', 'min:0', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'on_leave'])],
            'user_id' => ['nullable', 'uuid', 'exists:users,id', Rule::unique('teachers')->ignore($teacherId)],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'A teacher with this email already exists.',
            'experience_years.min' => 'Experience years must be at least 0.',
            'experience_years.max' => 'Experience years cannot exceed 50.',
            'base_salary.required_if' => 'Base salary is required for fixed salary type.',
            'hourly_rate.required_if' => 'Hourly rate is required for per-hour salary type.',
            'per_class_rate.required_if' => 'Per-class rate is required for per-class salary type.',
            'commission_percent.required_if' => 'Commission percent is required for commission salary type.',
            'commission_percent.max' => 'Commission percent cannot exceed 100.',
            'user_id.unique' => 'This user is already assigned to another teacher.',
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

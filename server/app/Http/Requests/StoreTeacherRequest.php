<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Teacher Request
 *
 * Validates teacher creation data.
 */
class StoreTeacherRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Modules\PeopleHr\Models\Teacher::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255', 'unique:teachers,email'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'specialization' => ['nullable', 'string', 'max:255'],
            'qualification' => ['nullable', 'string', 'max:255'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:50'],
            'salary_type' => ['required', Rule::in(['fixed', 'per_hour', 'per_class', 'commission'])],
            'base_salary' => ['required_if:salary_type,fixed', 'nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'hourly_rate' => ['required_if:salary_type,per_hour', 'nullable', 'numeric', 'min:0', 'max:99999.99'],
            'per_class_rate' => ['required_if:salary_type,per_class', 'nullable', 'numeric', 'min:0', 'max:99999.99'],
            'commission_percent' => ['required_if:salary_type,commission', 'nullable', 'numeric', 'min:0', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'on_leave'])],
            'join_date' => ['required', 'date', 'before_or_equal:today'],
            'branch_id' => ['required', 'uuid', 'exists:branches,id'],
            'user_id' => ['nullable', 'uuid', 'exists:users,id', 'unique:teachers,user_id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'Teacher name is required.',
            'email.unique' => 'A teacher with this email already exists.',
            'experience_years.min' => 'Experience years must be at least 0.',
            'experience_years.max' => 'Experience years cannot exceed 50.',
            'salary_type.required' => 'Salary type is required.',
            'salary_type.in' => 'Invalid salary type selected.',
            'base_salary.required_if' => 'Base salary is required for fixed salary type.',
            'base_salary.min' => 'Base salary must be at least 0.',
            'base_salary.max' => 'Base salary cannot exceed 9,999,999.99.',
            'hourly_rate.required_if' => 'Hourly rate is required for per-hour salary type.',
            'per_class_rate.required_if' => 'Per-class rate is required for per-class salary type.',
            'commission_percent.required_if' => 'Commission percent is required for commission salary type.',
            'commission_percent.max' => 'Commission percent cannot exceed 100.',
            'join_date.required' => 'Join date is required.',
            'join_date.before_or_equal' => 'Join date cannot be in the future.',
            'branch_id.exists' => 'Selected branch does not exist.',
            'user_id.unique' => 'This user is already assigned to another teacher.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'full_name' => 'teacher name',
            'phone' => 'phone number',
            'email' => 'email address',
            'specialization' => 'specialization',
            'qualification' => 'qualification',
            'experience_years' => 'years of experience',
            'salary_type' => 'salary type',
            'base_salary' => 'base salary',
            'hourly_rate' => 'hourly rate',
            'per_class_rate' => 'per-class rate',
            'commission_percent' => 'commission percentage',
            'join_date' => 'join date',
            'branch_id' => 'branch',
            'user_id' => 'user account',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default status if not provided
        if (!$this->has('status')) {
            $this->merge(['status' => 'active']);
        }

        // Trim string fields
        $this->merge(array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $this->all()));
    }
}

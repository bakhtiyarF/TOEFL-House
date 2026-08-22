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
            'email' => ['required', 'email', 'max:255', 'unique:teachers,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'address' => ['nullable', 'string', 'max:500'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'qualification' => ['required', 'string', 'max:255'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:50'],
            'hire_date' => ['required', 'date', 'before_or_equal:today'],
            'salary_type' => ['required', Rule::in(['fixed', 'hourly', 'per_class', 'commission'])],
            'base_salary' => ['required_if:salary_type,fixed', 'nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'hourly_rate' => ['required_if:salary_type,hourly', 'nullable', 'numeric', 'min:0', 'max:99999.99'],
            'per_class_rate' => ['required_if:salary_type,per_class', 'nullable', 'numeric', 'min:0', 'max:99999.99'],
            'commission_rate' => ['required_if:salary_type,commission', 'nullable', 'numeric', 'min:0', 'max:100'],
            'branch_id' => ['required', 'uuid', 'exists:branches,id'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'on_leave'])],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'Teacher name is required.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'A teacher with this email already exists.',
            'date_of_birth.before' => 'Date of birth must be in the past.',
            'qualification.required' => 'Qualification is required.',
            'hire_date.required' => 'Hire date is required.',
            'hire_date.before_or_equal' => 'Hire date cannot be in the future.',
            'salary_type.required' => 'Salary type is required.',
            'salary_type.in' => 'Invalid salary type selected.',
            'base_salary.required_if' => 'Base salary is required for fixed salary type.',
            'base_salary.min' => 'Base salary must be at least 0.',
            'base_salary.max' => 'Base salary cannot exceed 9,999,999.99.',
            'hourly_rate.required_if' => 'Hourly rate is required for hourly salary type.',
            'per_class_rate.required_if' => 'Per-class rate is required for per-class salary type.',
            'commission_rate.required_if' => 'Commission rate is required for commission salary type.',
            'commission_rate.max' => 'Commission rate cannot exceed 100%.',
            'branch_id.required' => 'Branch is required.',
            'branch_id.exists' => 'Selected branch does not exist.',
            'profile_photo.image' => 'Profile photo must be an image file.',
            'profile_photo.max' => 'Profile photo must not exceed 2MB.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate bank account information
            if ($this->bank_account_number && !$this->bank_name) {
                $validator->errors()->add('bank_name', 'Bank name is required when bank account number is provided.');
            }

            if ($this->bank_name && !$this->bank_account_number) {
                $validator->errors()->add('bank_account_number', 'Bank account number is required when bank name is provided.');
            }

            // Validate experience years
            if ($this->experience_years && $this->date_of_birth) {
                $age = now()->diffInYears($this->date_of_birth);
                
                if ($this->experience_years > $age - 18) {
                    $validator->errors()->add('experience_years', 'Experience years cannot exceed age minus 18.');
                }
            }

            // Check if branch has capacity for new teacher
            if ($this->branch_id) {
                $branch = \App\Modules\Iam\Models\Branch::find($this->branch_id);
                $activeTeachers = $branch->teachers()->where('status', 'active')->count();
                $maxTeachers = $branch->max_teachers ?? 50; // Default max

                if ($activeTeachers >= $maxTeachers) {
                    $validator->errors()->add('branch_id', "Branch has reached maximum teacher capacity ({$maxTeachers}).");
                }
            }
        });
    }
}

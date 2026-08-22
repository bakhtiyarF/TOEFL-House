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
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('teachers')->ignore($teacherId)],
            'phone' => ['nullable', 'string', 'max:20'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'qualifications' => ['nullable', 'string', 'max:2000'],
            'salary_type' => ['sometimes', Rule::in(['fixed', 'hourly', 'per_class', 'commission'])],
            'base_salary' => ['required_if:salary_type,fixed', 'sometimes', 'numeric', 'min:0', 'max:9999999.99'],
            'hourly_rate' => ['required_if:salary_type,hourly', 'nullable', 'numeric', 'min:0', 'max:99999.99'],
            'per_class_rate' => ['required_if:salary_type,per_class', 'nullable', 'numeric', 'min:0', 'max:99999.99'],
            'commission_rate' => ['required_if:salary_type,commission', 'nullable', 'numeric', 'min:0', 'max:100'],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'on_leave'])],
            'hire_date' => ['sometimes', 'date', 'before_or_equal:today'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'tax_id' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'A teacher with this email already exists.',
            'base_salary.required_if' => 'Base salary is required for fixed salary type.',
            'base_salary.min' => 'Base salary must be at least 0.',
            'base_salary.max' => 'Base salary cannot exceed 9,999,999.99.',
            'hourly_rate.required_if' => 'Hourly rate is required for hourly salary type.',
            'per_class_rate.required_if' => 'Per-class rate is required for per-class salary type.',
            'commission_rate.required_if' => 'Commission rate is required for commission salary type.',
            'commission_rate.max' => 'Commission rate cannot exceed 100%.',
            'hire_date.before_or_equal' => 'Hire date cannot be in the future.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $teacher = $this->route('teacher');

            // Check if teacher has active classes when trying to deactivate
            if ($this->status === 'inactive' && $teacher->status === 'active') {
                $activeClasses = $teacher->classes()->where('status', 'active')->count();
                
                if ($activeClasses > 0) {
                    $validator->errors()->add('status', "Cannot deactivate teacher with {$activeClasses} active class(es). Please reassign or cancel classes first.");
                }
            }

            // Validate salary type change
            if ($this->salary_type && $this->salary_type !== $teacher->salary_type) {
                // Check if teacher has active payroll records
                $activePayroll = $teacher->payrolls()
                    ->where('status', 'pending')
                    ->where('period_start', '>=', now()->startOfMonth())
                    ->exists();

                if ($activePayroll) {
                    $validator->errors()->add('salary_type', 'Cannot change salary type while there are pending payroll records for the current period.');
                }
            }

            // Validate bank account information
            if ($this->bank_account_number && !$this->bank_name) {
                $validator->errors()->add('bank_name', 'Bank name is required when bank account number is provided.');
            }

            if ($this->bank_name && !$this->bank_account_number) {
                $validator->errors()->add('bank_account_number', 'Bank account number is required when bank name is provided.');
            }
        });
    }
}

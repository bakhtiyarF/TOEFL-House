<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Enrollment Request
 *
 * Validates enrollment creation data.
 */
class StoreEnrollmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('enroll', \App\Modules\Academic\Models\Student::find($this->student_id)) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'uuid', 'exists:students,id'],
            'program_id' => ['required', 'uuid', 'exists:programs,id'],
            'program_version_id' => ['required', 'uuid', 'exists:program_versions,id'],
            'class_id' => ['nullable', 'uuid', 'exists:classes,id'],
            'enrollment_type' => ['required', Rule::in(['new', 'transfer', 're-enrollment', 'exchange'])],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'scholarship_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'skills_focus' => ['nullable', 'array'],
            'skills_focus.*' => ['string', 'max:100'],
            'started_at' => ['nullable', 'date', 'before_or_equal:today'],
            'branch_id' => ['required', 'uuid', 'exists:branches,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'student_id.required' => 'Student is required.',
            'student_id.exists' => 'Selected student does not exist.',
            'program_id.required' => 'Program is required.',
            'program_id.exists' => 'Selected program does not exist.',
            'program_version_id.required' => 'Program version is required.',
            'program_version_id.exists' => 'Selected program version does not exist.',
            'class_id.exists' => 'Selected class does not exist.',
            'enrollment_type.required' => 'Enrollment type is required.',
            'enrollment_type.in' => 'Invalid enrollment type selected.',
            'discount_percent.min' => 'Discount percent must be at least 0.',
            'discount_percent.max' => 'Discount percent cannot exceed 100.',
            'scholarship_percent.min' => 'Scholarship percent must be at least 0.',
            'scholarship_percent.max' => 'Scholarship percent cannot exceed 100.',
            'started_at.before_or_equal' => 'Start date cannot be in the future.',
            'branch_id.exists' => 'Selected branch does not exist.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'student_id' => 'student',
            'program_id' => 'program',
            'program_version_id' => 'program version',
            'class_id' => 'class',
            'enrollment_type' => 'enrollment type',
            'discount_percent' => 'discount percentage',
            'scholarship_percent' => 'scholarship percentage',
            'started_at' => 'start date',
            'branch_id' => 'branch',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Check if total discount + scholarship doesn't exceed 100%
            $discount = $this->discount_percent ?? 0;
            $scholarship = $this->scholarship_percent ?? 0;
            
            if ($discount + $scholarship > 100) {
                $validator->errors()->add(
                    'discount_percent',
                    'Total discount and scholarship cannot exceed 100%.'
                );
            }

            // Check if class belongs to the program version
            if ($this->class_id && $this->program_version_id) {
                $class = \App\Modules\Academic\Models\AcademicClass::find($this->class_id);
                if ($class && $class->program_version_id !== $this->program_version_id) {
                    $validator->errors()->add(
                        'class_id',
                        'Selected class does not belong to the selected program version.'
                    );
                }
            }

            // Check if class has available capacity
            if ($this->class_id) {
                $class = \App\Modules\Academic\Models\AcademicClass::find($this->class_id);
                if ($class && $class->isFull()) {
                    $validator->errors()->add(
                        'class_id',
                        'Selected class is already full.'
                    );
                }
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default enrollment type if not provided
        if (!$this->has('enrollment_type')) {
            $this->merge(['enrollment_type' => 'new']);
        }

        // Trim string fields
        $this->merge(array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $this->all()));
    }
}

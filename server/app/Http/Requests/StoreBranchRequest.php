<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Branch Request
 *
 * Validates branch creation data.
 */
class StoreBranchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Modules\Iam\Models\Branch::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:branches,code'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'timezone' => ['required', 'string', 'max:50'],
            'currency' => ['required', 'string', 'size:3'],
            'language' => ['required', 'string', 'size:2'],
            'max_students' => ['nullable', 'integer', 'min:1', 'max:99999'],
            'max_teachers' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'max_classes' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'suspended'])],
            'logo' => ['nullable', 'image', 'max:2048'],
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Branch name is required.',
            'code.required' => 'Branch code is required.',
            'code.unique' => 'A branch with this code already exists.',
            'address.required' => 'Address is required.',
            'city.required' => 'City is required.',
            'country.required' => 'Country is required.',
            'timezone.required' => 'Timezone is required.',
            'currency.required' => 'Currency is required.',
            'currency.size' => 'Currency must be a 3-letter code (e.g., USD, EUR).',
            'language.required' => 'Language is required.',
            'language.size' => 'Language must be a 2-letter code (e.g., en, es).',
            'max_students.min' => 'Maximum students must be at least 1.',
            'max_students.max' => 'Maximum students cannot exceed 99,999.',
            'max_teachers.min' => 'Maximum teachers must be at least 1.',
            'max_teachers.max' => 'Maximum teachers cannot exceed 9,999.',
            'max_classes.min' => 'Maximum classes must be at least 1.',
            'max_classes.max' => 'Maximum classes cannot exceed 9,999.',
            'logo.image' => 'Logo must be an image file.',
            'logo.max' => 'Logo must not exceed 2MB.',
            'organization_id.required' => 'Organization is required.',
            'organization_id.exists' => 'Selected organization does not exist.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate timezone
            if ($this->timezone) {
                try {
                    new \DateTimeZone($this->timezone);
                } catch (\Exception $e) {
                    $validator->errors()->add('timezone', 'Invalid timezone selected.');
                }
            }

            // Validate capacity limits
            if ($this->max_students && $this->max_teachers) {
                $studentPerTeacher = $this->max_students / $this->max_teachers;
                
                if ($studentPerTeacher > 100) {
                    $validator->errors()->add('max_students', 'Student to teacher ratio cannot exceed 100:1.');
                }
            }

            // Check if organization has capacity for new branch
            if ($this->organization_id) {
                $organization = \App\Modules\Iam\Models\Organization::find($this->organization_id);
                $activeBranches = $organization->branches()->where('status', 'active')->count();
                $maxBranches = $organization->max_branches ?? 100; // Default max

                if ($activeBranches >= $maxBranches) {
                    $validator->errors()->add('organization_id', "Organization has reached maximum branch capacity ({$maxBranches}).");
                }
            }
        });
    }
}

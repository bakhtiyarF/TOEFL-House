<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Donor Request
 *
 * Validates donor creation data.
 */
class StoreDonorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Modules\FundingImpact\Models\Donor::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:donors,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'donor_type' => ['required', Rule::in(['individual', 'organization', 'foundation', 'corporation', 'government'])],
            'organization_name' => ['required_if:donor_type,organization,foundation,corporation', 'nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'preferred_contact_method' => ['nullable', Rule::in(['email', 'phone', 'mail'])],
            'communication_frequency' => ['nullable', Rule::in(['weekly', 'monthly', 'quarterly', 'annually'])],
            'notes' => ['nullable', 'string', 'max:5000'],
            'is_anonymous' => ['nullable', 'boolean'],
            'branch_id' => ['required', 'uuid', 'exists:branches,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'Donor name is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'A donor with this email already exists.',
            'donor_type.required' => 'Donor type is required.',
            'donor_type.in' => 'Invalid donor type selected.',
            'organization_name.required_if' => 'Organization name is required for organization, foundation, or corporation donors.',
            'preferred_contact_method.in' => 'Invalid contact method selected.',
            'communication_frequency.in' => 'Invalid communication frequency selected.',
            'notes.max' => 'Notes cannot exceed 5,000 characters.',
            'branch_id.required' => 'Branch is required.',
            'branch_id.exists' => 'Selected branch does not exist.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate that at least one contact method is provided
            if (!$this->email && !$this->phone && !$this->address) {
                $validator->errors()->add('email', 'At least one contact method (email, phone, or address) is required.');
            }

            // Validate anonymous donor requirements
            if ($this->is_anonymous) {
                if ($this->donor_type === 'organization' || $this->donor_type === 'corporation') {
                    $validator->errors()->add('is_anonymous', 'Organizations and corporations cannot be anonymous donors.');
                }
            }

            // Check for duplicate donor
            if ($this->full_name && $this->email) {
                $existingDonor = \App\Modules\FundingImpact\Models\Donor::where('full_name', $this->full_name)
                    ->where('email', $this->email)
                    ->exists();

                if ($existingDonor) {
                    $validator->errors()->add('full_name', 'A donor with this name and email already exists.');
                }
            }
        });
    }
}

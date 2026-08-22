<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Visitor Request
 *
 * Validates visitor/lead creation data.
 */
class StoreVisitorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Modules\CrmEnrollment\Models\Visitor::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'father_name' => ['nullable', 'string', 'max:255'],
            'address_region' => ['nullable', 'string', 'max:255'],
            'tazkira_no' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'dob' => ['nullable', 'date', 'before:today'],
            'school_or_university' => ['nullable', 'string', 'max:255'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'source' => ['required', Rule::in(['walk_in', 'referral', 'social_media', 'website', 'phone', 'event', 'other'])],
            'campaign_id' => ['nullable', 'uuid', 'exists:campaigns,id'],
            'stage' => ['nullable', Rule::in(['lead', 'contacted', 'interested', 'placement_test', 'placement_completed', 'enrollment', 'converted'])],
            'interested_program' => ['nullable', 'string', 'max:255'],
            'follow_up_date' => ['nullable', 'date', 'after_or_equal:today'],
            'follow_up_notes' => ['nullable', 'string', 'max:2000'],
            'assigned_to' => ['nullable', 'uuid', 'exists:users,id'],
            'visit_date' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'branch_id' => ['required', 'uuid', 'exists:branches,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'Visitor name is required.',
            'phone.required' => 'Phone number is required.',
            'dob.before' => 'Date of birth must be in the past.',
            'source.required' => 'Lead source is required.',
            'source.in' => 'Invalid lead source selected.',
            'campaign_id.exists' => 'Selected campaign does not exist.',
            'stage.in' => 'Invalid stage selected.',
            'follow_up_date.after_or_equal' => 'Follow-up date must be today or in the future.',
            'visit_date.before_or_equal' => 'Visit date cannot be in the future.',
            'branch_id.exists' => 'Selected branch does not exist.',
            'assigned_to.exists' => 'Selected user does not exist.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'full_name' => 'visitor name',
            'phone' => 'phone number',
            'email' => 'email address',
            'father_name' => 'father\'s name',
            'address_region' => 'address',
            'tazkira_no' => 'Tazkira number',
            'dob' => 'date of birth',
            'emergency_contact_name' => 'emergency contact name',
            'emergency_contact_phone' => 'emergency contact phone',
            'source' => 'lead source',
            'campaign_id' => 'campaign',
            'stage' => 'pipeline stage',
            'interested_program' => 'interested program',
            'follow_up_date' => 'follow-up date',
            'follow_up_notes' => 'follow-up notes',
            'assigned_to' => 'assigned user',
            'visit_date' => 'visit date',
            'branch_id' => 'branch',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default stage if not provided
        if (!$this->has('stage')) {
            $this->merge(['stage' => 'lead']);
        }

        // Set default status
        if (!$this->has('status')) {
            $this->merge(['status' => 'active']);
        }

        // Trim string fields
        $this->merge(array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $this->all()));
    }
}

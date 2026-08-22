<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Donation Request
 *
 * Validates donation creation data.
 */
class StoreDonationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Modules\FundingImpact\Models\Donation::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'donor_id' => ['required', 'uuid', 'exists:donors,id'],
            'campaign_id' => ['nullable', 'uuid', 'exists:campaigns,id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'currency' => ['nullable', 'string', 'size:3'],
            'donation_date' => ['required', 'date', 'before_or_equal:today'],
            'payment_method' => ['required', Rule::in(['cash', 'check', 'bank_transfer', 'credit_card', 'online', 'other'])],
            'is_recurring' => ['nullable', 'boolean'],
            'recurrence_frequency' => ['required_if:is_recurring,true', 'nullable', Rule::in(['weekly', 'monthly', 'quarterly', 'yearly'])],
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
            'donor_id.required' => 'Donor is required.',
            'donor_id.exists' => 'Selected donor does not exist.',
            'campaign_id.exists' => 'Selected campaign does not exist.',
            'amount.required' => 'Donation amount is required.',
            'amount.min' => 'Donation amount must be at least 0.01.',
            'amount.max' => 'Donation amount cannot exceed 99,999,999.99.',
            'currency.size' => 'Currency must be a 3-letter code (e.g., USD, EUR).',
            'donation_date.required' => 'Donation date is required.',
            'donation_date.before_or_equal' => 'Donation date cannot be in the future.',
            'payment_method.required' => 'Payment method is required.',
            'payment_method.in' => 'Invalid payment method selected.',
            'recurrence_frequency.required_if' => 'Recurrence frequency is required for recurring donations.',
            'recurrence_frequency.in' => 'Invalid recurrence frequency selected.',
            'branch_id.exists' => 'Selected branch does not exist.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'donor_id' => 'donor',
            'campaign_id' => 'campaign',
            'amount' => 'donation amount',
            'currency' => 'currency',
            'donation_date' => 'donation date',
            'payment_method' => 'payment method',
            'is_recurring' => 'recurring donation',
            'recurrence_frequency' => 'recurrence frequency',
            'branch_id' => 'branch',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Check if campaign belongs to the same branch
            if ($this->campaign_id && $this->branch_id) {
                $campaign = \App\Modules\FundingImpact\Models\Campaign::find($this->campaign_id);
                if ($campaign && $campaign->branch_id !== $this->branch_id) {
                    $validator->errors()->add(
                        'campaign_id',
                        'Selected campaign does not belong to the selected branch.'
                    );
                }
            }

            // Check if donor has email for receipt
            if ($this->donor_id) {
                $donor = \App\Modules\FundingImpact\Models\Donor::find($this->donor_id);
                if ($donor && !$donor->email) {
                    // This is a warning, not an error
                    // Could be logged or shown as a flash message
                }
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default currency if not provided
        if (!$this->has('currency')) {
            $this->merge(['currency' => 'USD']);
        }

        // Set default status
        if (!$this->has('status')) {
            $this->merge(['status' => 'completed']);
        }

        // Set default is_recurring
        if (!$this->has('is_recurring')) {
            $this->merge(['is_recurring' => false]);
        }

        // Trim string fields
        $this->merge(array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $this->all()));
    }
}

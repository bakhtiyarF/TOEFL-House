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
            'campaign_id' => ['nullable', 'uuid', 'exists:funding_campaigns,id'],
            'branch_id' => ['required', 'uuid', 'exists:branches,id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'currency' => ['nullable', 'string', 'size:3'],
            'donation_date' => ['required', 'date', 'before_or_equal:today'],
            'payment_method' => ['required', Rule::in(['cash', 'check', 'bank_transfer', 'credit_card', 'online', 'other'])],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'receipt_number' => ['nullable', 'string', 'max:100', 'unique:donations,receipt_number'],
            'is_recurring' => ['nullable', 'boolean'],
            'recurrence_frequency' => ['required_if:is_recurring,true', 'nullable', Rule::in(['weekly', 'monthly', 'quarterly', 'yearly'])],
            'recurrence_end_date' => ['required_if:is_recurring,true', 'nullable', 'date', 'after:donation_date'],
            'designation' => ['nullable', Rule::in(['general', 'scholarship', 'infrastructure', 'program', 'other'])],
            'designation_note' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'send_receipt' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'donor_id.exists' => 'Selected donor does not exist.',
            'campaign_id.exists' => 'Selected campaign does not exist.',
            'branch_id.exists' => 'Selected branch does not exist.',
            'amount.min' => 'Donation amount must be at least 0.01.',
            'amount.max' => 'Donation amount cannot exceed 99,999,999.99.',
            'currency.size' => 'Currency must be a 3-letter code (e.g., USD, EUR).',
            'donation_date.before_or_equal' => 'Donation date cannot be in the future.',
            'payment_method.in' => 'Invalid payment method selected.',
            'receipt_number.unique' => 'A donation with this receipt number already exists.',
            'recurrence_frequency.required_if' => 'Recurrence frequency is required for recurring donations.',
            'recurrence_frequency.in' => 'Invalid recurrence frequency selected.',
            'recurrence_end_date.after' => 'Recurrence end date must be after donation date.',
            'designation.in' => 'Invalid designation selected.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Check if campaign belongs to the branch
            if ($this->campaign_id && $this->branch_id) {
                $campaign = \App\Modules\FundingImpact\Models\FundingCampaign::find($this->campaign_id);
                
                if ($campaign && $campaign->branch_id !== $this->branch_id) {
                    $validator->errors()->add('campaign_id', 'Campaign does not belong to the selected branch.');
                }
            }

            // Check if donor has email for receipt
            if ($this->send_receipt && $this->donor_id) {
                $donor = \App\Modules\FundingImpact\Models\Donor::find($this->donor_id);
                
                if ($donor && !$donor->email) {
                    $validator->errors()->add('send_receipt', 'Donor does not have an email address for receipt delivery.');
                }
            }

            // Check if campaign is active
            if ($this->campaign_id) {
                $campaign = \App\Modules\FundingImpact\Models\FundingCampaign::find($this->campaign_id);
                
                if ($campaign && $campaign->status !== 'active') {
                    $validator->errors()->add('campaign_id', 'Campaign is not active.');
                }

                // Check if donation would exceed campaign goal
                if ($campaign && $campaign->goal_amount) {
                    $totalDonations = $campaign->donations()->sum('amount') + ($this->amount ?? 0);
                    
                    if ($totalDonations > $campaign->goal_amount * 1.1) { // Allow 10% over goal
                        $validator->errors()->add('amount', 'Donation would significantly exceed campaign goal.');
                    }
                }
            }
        });
    }
}

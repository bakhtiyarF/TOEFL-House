<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Payment Request
 *
 * Validates payment creation data.
 */
class StorePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Modules\FinancePayroll\Models\Payment::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'uuid', 'exists:students,id'],
            'invoice_id' => ['nullable', 'uuid', 'exists:invoices,id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'payment_method' => ['required', Rule::in(['cash', 'card', 'bank_transfer', 'check', 'online'])],
            'category' => ['required', Rule::in(['tuition', 'registration', 'exam', 'book', 'card', 'placement', 'diploma', 'other'])],
            'status' => ['nullable', Rule::in(['pending', 'completed', 'failed', 'refunded'])],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'semester' => ['nullable', 'string', 'max:50'],
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
            'invoice_id.exists' => 'Selected invoice does not exist.',
            'amount.required' => 'Payment amount is required.',
            'amount.min' => 'Payment amount must be at least 0.01.',
            'amount.max' => 'Payment amount cannot exceed 9,999,999.99.',
            'payment_method.required' => 'Payment method is required.',
            'payment_method.in' => 'Invalid payment method selected.',
            'category.required' => 'Payment category is required.',
            'category.in' => 'Invalid payment category selected.',
            'date.required' => 'Payment date is required.',
            'date.before_or_equal' => 'Payment date cannot be in the future.',
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
            'invoice_id' => 'invoice',
            'amount' => 'payment amount',
            'payment_method' => 'payment method',
            'category' => 'payment category',
            'date' => 'payment date',
            'branch_id' => 'branch',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default status if not provided
        if (!$this->has('status')) {
            $this->merge(['status' => 'completed']);
        }

        // Trim string fields
        $this->merge(array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $this->all()));
    }
}

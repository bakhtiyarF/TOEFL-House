<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Invoice Request
 *
 * Validates invoice update data.
 */
class UpdateInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $invoice = $this->route('invoice');
        return $this->user()?->can('update', $invoice) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'due_date' => ['sometimes', 'date', 'after_or_equal:invoice_date'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.description' => ['required_with:items', 'string', 'max:500'],
            'items.*.quantity' => ['required_with:items', 'numeric', 'min:1', 'max:999999'],
            'items.*.unit_price' => ['required_with:items', 'numeric', 'min:0', 'max:9999999.99'],
            'discount_amount' => ['sometimes', 'numeric', 'min:0', 'max:9999999.99'],
            'tax_amount' => ['sometimes', 'numeric', 'min:0', 'max:9999999.99'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['sometimes', Rule::in(['draft', 'issued', 'paid', 'overdue', 'cancelled'])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'due_date.after_or_equal' => 'Due date must be on or after invoice date.',
            'items.min' => 'At least one invoice item is required.',
            'items.*.description.required_with' => 'Item description is required.',
            'items.*.quantity.min' => 'Item quantity must be at least 1.',
            'items.*.unit_price.min' => 'Item unit price must be at least 0.',
            'discount_amount.min' => 'Discount amount must be at least 0.',
            'tax_amount.min' => 'Tax amount must be at least 0.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $invoice = $this->route('invoice');

            // Prevent editing paid invoices
            if ($invoice->status === 'paid') {
                $validator->errors()->add('status', 'Cannot edit a paid invoice.');
                return;
            }

            // Prevent editing cancelled invoices
            if ($invoice->status === 'cancelled') {
                $validator->errors()->add('status', 'Cannot edit a cancelled invoice.');
                return;
            }

            // Calculate totals and validate
            if ($this->items) {
                $subtotal = 0;
                foreach ($this->items as $item) {
                    $subtotal += ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0);
                }

                $discount = $this->discount_amount ?? $invoice->discount_amount ?? 0;
                $tax = $this->tax_amount ?? $invoice->tax_amount ?? 0;
                $total = $subtotal - $discount + $tax;

                if ($total < 0) {
                    $validator->errors()->add('discount_amount', 'Discount cannot exceed subtotal.');
                }

                // Check if invoice has payments
                $paidAmount = $invoice->payments()->where('status', 'completed')->sum('amount');
                
                if ($paidAmount > 0 && $total < $paidAmount) {
                    $validator->errors()->add('items', "Cannot reduce invoice total below paid amount ({$paidAmount} AFN).");
                }
            }

            // Validate status transitions
            if ($this->status) {
                $allowedTransitions = [
                    'draft' => ['issued', 'cancelled'],
                    'issued' => ['paid', 'overdue', 'cancelled'],
                    'overdue' => ['paid', 'cancelled'],
                ];

                $currentStatus = $invoice->status;
                $newStatus = $this->status;

                if (isset($allowedTransitions[$currentStatus])) {
                    if (!in_array($newStatus, $allowedTransitions[$currentStatus])) {
                        $validator->errors()->add('status', "Cannot transition from '{$currentStatus}' to '{$newStatus}'.");
                    }
                }
            }
        });
    }
}

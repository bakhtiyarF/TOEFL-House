<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Invoice Request
 *
 * Validates invoice creation data.
 */
class StoreInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Modules\FinancePayroll\Models\Invoice::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'uuid', 'exists:students,id'],
            'enrollment_id' => ['nullable', 'uuid', 'exists:enrollments,id'],
            'branch_id' => ['required', 'uuid', 'exists:branches,id'],
            'invoice_date' => ['required', 'date', 'before_or_equal:today'],
            'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.quantity' => ['required', 'numeric', 'min:1', 'max:999999'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'discount_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'tax_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', Rule::in(['draft', 'issued', 'paid', 'overdue', 'cancelled'])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'student_id.exists' => 'Selected student does not exist.',
            'enrollment_id.exists' => 'Selected enrollment does not exist.',
            'branch_id.exists' => 'Selected branch does not exist.',
            'invoice_date.before_or_equal' => 'Invoice date cannot be in the future.',
            'due_date.after_or_equal' => 'Due date must be on or after invoice date.',
            'items.required' => 'At least one invoice item is required.',
            'items.min' => 'At least one invoice item is required.',
            'items.*.description.required' => 'Item description is required.',
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
            // Calculate totals and validate
            if ($this->items) {
                $subtotal = 0;
                foreach ($this->items as $item) {
                    $subtotal += ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0);
                }

                $discount = $this->discount_amount ?? 0;
                $tax = $this->tax_amount ?? 0;
                $total = $subtotal - $discount + $tax;

                if ($total < 0) {
                    $validator->errors()->add('discount_amount', 'Discount cannot exceed subtotal.');
                }

                // Check if student belongs to the branch
                if ($this->student_id && $this->branch_id) {
                    $student = \App\Modules\Academic\Models\Student::find($this->student_id);
                    
                    if ($student && $student->branch_id !== $this->branch_id) {
                        $validator->errors()->add('student_id', 'Student does not belong to the selected branch.');
                    }
                }

                // Check if enrollment belongs to student
                if ($this->enrollment_id && $this->student_id) {
                    $enrollment = \App\Modules\Academic\Models\Enrollment::find($this->enrollment_id);
                    
                    if ($enrollment && $enrollment->student_id !== $this->student_id) {
                        $validator->errors()->add('enrollment_id', 'Enrollment does not belong to the selected student.');
                    }
                }
            }
        });
    }
}

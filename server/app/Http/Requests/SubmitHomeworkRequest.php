<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Submit Homework Request
 *
 * Validates homework submission data.
 */
class SubmitHomeworkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $homework = $this->route('homework');
        return $this->user()?->can('submit', $homework) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'submission_text' => ['required_without:attachments', 'nullable', 'string', 'max:10000'],
            'attachments' => ['required_without:submission_text', 'nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:20480'], // 20MB max
            'comments' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'submission_text.required_without' => 'Either submission text or attachments are required.',
            'attachments.required_without' => 'Either submission text or attachments are required.',
            'attachments.max' => 'Maximum 5 attachments allowed.',
            'attachments.*.max' => 'Each attachment must not exceed 20MB.',
            'submission_text.max' => 'Submission text cannot exceed 10,000 characters.',
            'comments.max' => 'Comments cannot exceed 2,000 characters.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $homework = $this->route('homework');
            $student = $this->user()->student;

            // Check if homework is past due date
            if ($homework && $homework->due_date->isPast()) {
                // Allow late submission but mark it
                $this->merge(['is_late' => true]);
            }

            // Check if student has already submitted
            if ($homework && $student) {
                $existingSubmission = $homework->submissions()
                    ->where('student_id', $student->id)
                    ->first();

                if ($existingSubmission && !$homework->allow_resubmission) {
                    $validator->errors()->add('homework', 'You have already submitted this homework.');
                }
            }

            // Check if student is enrolled in the class
            if ($homework && $student) {
                $isEnrolled = $homework->class->students()
                    ->where('student_id', $student->id)
                    ->where('status', 'active')
                    ->exists();

                if (!$isEnrolled) {
                    $validator->errors()->add('homework', 'You are not enrolled in this class.');
                }
            }
        });
    }
}

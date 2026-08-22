<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Homework Request
 *
 * Validates homework creation data.
 */
class StoreHomeworkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Modules\Academic\Models\Homework::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'class_id' => ['required', 'uuid', 'exists:classes,id'],
            'session_id' => ['nullable', 'uuid', 'exists:sessions,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'due_date' => ['required', 'date', 'after:today'],
            'max_points' => ['required', 'numeric', 'min:1', 'max:1000'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:10240'], // 10MB max
            'is_graded' => ['nullable', 'boolean'],
            'rubric' => ['nullable', 'array'],
            'rubric.*.criteria' => ['required_with:rubric', 'string', 'max:255'],
            'rubric.*.points' => ['required_with:rubric', 'numeric', 'min:0'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'class_id.exists' => 'Selected class does not exist.',
            'session_id.exists' => 'Selected session does not exist.',
            'title.required' => 'Homework title is required.',
            'description.required' => 'Homework description is required.',
            'due_date.after' => 'Due date must be in the future.',
            'max_points.min' => 'Maximum points must be at least 1.',
            'max_points.max' => 'Maximum points cannot exceed 1000.',
            'attachments.max' => 'Maximum 10 attachments allowed.',
            'attachments.*.max' => 'Each attachment must not exceed 10MB.',
            'rubric.*.criteria.required_with' => 'Rubric criteria is required.',
            'rubric.*.points.required_with' => 'Rubric points is required.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Check if session belongs to the class
            if ($this->session_id && $this->class_id) {
                $session = \App\Modules\Academic\Models\Session::find($this->session_id);
                
                if ($session && $session->class_id !== $this->class_id) {
                    $validator->errors()->add('session_id', 'Session does not belong to the selected class.');
                }
            }

            // Validate rubric total points
            if ($this->rubric && is_array($this->rubric)) {
                $totalRubricPoints = collect($this->rubric)->sum('points');
                
                if ($totalRubricPoints > $this->max_points) {
                    $validator->errors()->add('rubric', "Total rubric points ({$totalRubricPoints}) cannot exceed maximum points ({$this->max_points}).");
                }
            }

            // Check if user is the teacher of the class
            if ($this->class_id) {
                $class = \App\Modules\Academic\Models\AcademicClass::find($this->class_id);
                $user = $this->user();
                
                if ($class && $user->teacher && $class->teacher_id !== $user->teacher->id) {
                    if (!$user->hasRole(['admin', 'academic_coordinator'])) {
                        $validator->errors()->add('class_id', 'You are not authorized to create homework for this class.');
                    }
                }
            }
        });
    }
}

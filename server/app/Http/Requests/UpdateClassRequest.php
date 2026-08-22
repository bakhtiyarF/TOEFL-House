<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Class Request
 *
 * Validates class update data.
 */
class UpdateClassRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $class = $this->route('class');
        return $this->user()?->can('update', $class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $classId = $this->route('class')?->id;
        
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('classes')->ignore($classId)],
            'description' => ['nullable', 'string', 'max:2000'],
            'program_id' => ['sometimes', 'uuid', 'exists:programs,id'],
            'level_id' => ['sometimes', 'uuid', 'exists:levels,id'],
            'teacher_id' => ['sometimes', 'uuid', 'exists:teachers,id'],
            'max_capacity' => ['sometimes', 'integer', 'min:1', 'max:200'],
            'min_capacity' => ['nullable', 'integer', 'min:1', 'max:200'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after:start_date'],
            'schedule' => ['sometimes', 'array'],
            'schedule.*.day' => ['required_with:schedule', Rule::in(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])],
            'schedule.*.start_time' => ['required_with:schedule', 'date_format:H:i'],
            'schedule.*.end_time' => ['required_with:schedule', 'date_format:H:i'],
            'fee' => ['sometimes', 'numeric', 'min:0', 'max:999999.99'],
            'status' => ['sometimes', Rule::in(['draft', 'scheduled', 'active', 'completed', 'cancelled'])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'code.unique' => 'A class with this code already exists.',
            'program_id.exists' => 'Selected program does not exist.',
            'level_id.exists' => 'Selected level does not exist.',
            'teacher_id.exists' => 'Selected teacher does not exist.',
            'max_capacity.min' => 'Maximum capacity must be at least 1.',
            'max_capacity.max' => 'Maximum capacity cannot exceed 200.',
            'end_date.after' => 'End date must be after start date.',
            'schedule.*.day.in' => 'Invalid day of week selected.',
            'schedule.*.start_time.date_format' => 'Start time must be in HH:MM format.',
            'schedule.*.end_time.date_format' => 'End time must be in HH:MM format.',
            'fee.min' => 'Fee must be at least 0.',
            'fee.max' => 'Fee cannot exceed 999,999.99.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $class = $this->route('class');

            // Check if teacher is available for the schedule
            if ($this->teacher_id) {
                $teacher = \App\Modules\PeopleHr\Models\Teacher::find($this->teacher_id);
                
                if ($teacher && $teacher->status !== 'active') {
                    $validator->errors()->add('teacher_id', 'Selected teacher is not active.');
                }
            }

            // Check if program and level match
            if ($this->program_id && $this->level_id) {
                $level = \App\Modules\Academic\Models\Level::find($this->level_id);
                
                if ($level && $level->program_id !== $this->program_id) {
                    $validator->errors()->add('level_id', 'Selected level does not belong to the selected program.');
                }
            }

            // Check for schedule conflicts with teacher
            if ($this->teacher_id && $this->schedule && $this->start_date && $this->end_date) {
                $conflicts = \App\Modules\Academic\Models\AcademicClass::where('teacher_id', $this->teacher_id)
                    ->where('id', '!=', $class->id)
                    ->where(function ($query) {
                        $query->whereBetween('start_date', [$this->start_date, $this->end_date])
                            ->orWhereBetween('end_date', [$this->start_date, $this->end_date])
                            ->orWhere(function ($q) {
                                $q->where('start_date', '<=', $this->start_date)
                                    ->where('end_date', '>=', $this->end_date);
                            });
                    })
                    ->where('status', '!=', 'cancelled')
                    ->exists();

                if ($conflicts) {
                    $validator->errors()->add('teacher_id', 'Teacher has scheduling conflicts with existing classes.');
                }
            }

            // Prevent changing status from completed/cancelled
            if ($this->status && in_array($class->status, ['completed', 'cancelled'])) {
                $validator->errors()->add('status', 'Cannot change status of completed or cancelled class.');
            }

            // Check if reducing capacity below current enrollment
            if ($this->max_capacity && $this->max_capacity < $class->max_capacity) {
                $currentEnrollment = $class->enrollments()->where('status', 'active')->count();
                
                if ($this->max_capacity < $currentEnrollment) {
                    $validator->errors()->add('max_capacity', "Cannot reduce capacity below current enrollment ({$currentEnrollment} students).");
                }
            }
        });
    }
}

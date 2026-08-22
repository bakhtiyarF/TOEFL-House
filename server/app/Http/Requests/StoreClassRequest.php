<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Class Request
 *
 * Validates class creation data.
 */
class StoreClassRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Modules\Academic\Models\AcademicClass::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:classes,code'],
            'description' => ['nullable', 'string', 'max:2000'],
            'program_id' => ['required', 'uuid', 'exists:programs,id'],
            'level_id' => ['required', 'uuid', 'exists:levels,id'],
            'teacher_id' => ['required', 'uuid', 'exists:teachers,id'],
            'branch_id' => ['required', 'uuid', 'exists:branches,id'],
            'max_capacity' => ['required', 'integer', 'min:1', 'max:200'],
            'min_capacity' => ['nullable', 'integer', 'min:1', 'max:200', 'lte:max_capacity'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'schedule' => ['required', 'array'],
            'schedule.*.day' => ['required', Rule::in(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])],
            'schedule.*.start_time' => ['required', 'date_format:H:i'],
            'schedule.*.end_time' => ['required', 'date_format:H:i', 'after:schedule.*.start_time'],
            'fee' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'status' => ['nullable', Rule::in(['draft', 'scheduled', 'active', 'completed', 'cancelled'])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Class name is required.',
            'code.required' => 'Class code is required.',
            'code.unique' => 'A class with this code already exists.',
            'program_id.exists' => 'Selected program does not exist.',
            'level_id.exists' => 'Selected level does not exist.',
            'teacher_id.exists' => 'Selected teacher does not exist.',
            'branch_id.exists' => 'Selected branch does not exist.',
            'max_capacity.min' => 'Maximum capacity must be at least 1.',
            'max_capacity.max' => 'Maximum capacity cannot exceed 200.',
            'min_capacity.lte' => 'Minimum capacity must be less than or equal to maximum capacity.',
            'start_date.after_or_equal' => 'Start date must be today or in the future.',
            'end_date.after' => 'End date must be after start date.',
            'schedule.required' => 'Class schedule is required.',
            'schedule.*.day.in' => 'Invalid day of week selected.',
            'schedule.*.start_time.date_format' => 'Start time must be in HH:MM format.',
            'schedule.*.end_time.after' => 'End time must be after start time.',
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
            // Check if teacher is available for the schedule
            if ($this->teacher_id && $this->schedule) {
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
                    ->where('id', '!=', $this->route('class')?->id ?? '')
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
        });
    }
}

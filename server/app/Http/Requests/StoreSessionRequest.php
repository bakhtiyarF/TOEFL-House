<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Session Request
 *
 * Validates session creation data.
 */
class StoreSessionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('manageSessions', \App\Modules\Academic\Models\AcademicClass::find($this->class_id)) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'class_id' => ['required', 'uuid', 'exists:classes,id'],
            'session_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'topic' => ['nullable', 'string', 'max:500'],
            'teacher_id' => ['nullable', 'uuid', 'exists:teachers,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'class_id.required' => 'Class is required.',
            'class_id.exists' => 'Selected class does not exist.',
            'session_date.required' => 'Session date is required.',
            'session_date.after_or_equal' => 'Session date must be today or in the future.',
            'start_time.required' => 'Start time is required.',
            'start_time.date_format' => 'Start time must be in HH:MM format.',
            'end_time.required' => 'End time is required.',
            'end_time.date_format' => 'End time must be in HH:MM format.',
            'end_time.after' => 'End time must be after start time.',
            'teacher_id.exists' => 'Selected teacher does not exist.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'class_id' => 'class',
            'session_date' => 'session date',
            'start_time' => 'start time',
            'end_time' => 'end time',
            'topic' => 'topic',
            'teacher_id' => 'teacher',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Check if teacher belongs to the class
            if ($this->teacher_id && $this->class_id) {
                $class = \App\Modules\Academic\Models\AcademicClass::find($this->class_id);
                if ($class && $class->teacher_id !== $this->teacher_id) {
                    $validator->errors()->add(
                        'teacher_id',
                        'Selected teacher is not assigned to this class.'
                    );
                }
            }

            // Check if session overlaps with existing sessions
            if ($this->class_id && $this->session_date && $this->start_time && $this->end_time) {
                $overlapping = \App\Modules\Academic\Models\Session::where('class_id', $this->class_id)
                    ->where('session_date', $this->session_date)
                    ->where(function ($query) {
                        $query->whereBetween('start_time', [$this->start_time, $this->end_time])
                              ->orWhereBetween('end_time', [$this->start_time, $this->end_time])
                              ->orWhere(function ($q) {
                                  $q->where('start_time', '<=', $this->start_time)
                                    ->where('end_time', '>=', $this->end_time);
                              });
                    })
                    ->exists();

                if ($overlapping) {
                    $validator->errors()->add(
                        'start_time',
                        'Session time overlaps with an existing session.'
                    );
                }
            }

            // Check session duration (max 4 hours)
            if ($this->start_time && $this->end_time) {
                $start = \Carbon\Carbon::createFromFormat('H:i', $this->start_time);
                $end = \Carbon\Carbon::createFromFormat('H:i', $this->end_time);
                $duration = $start->diffInMinutes($end);

                if ($duration > 240) {
                    $validator->errors()->add(
                        'end_time',
                        'Session duration cannot exceed 4 hours.'
                    );
                }
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default status
        if (!$this->has('status')) {
            $this->merge(['status' => 'scheduled']);
        }

        // Trim string fields
        $this->merge(array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $this->all()));
    }
}

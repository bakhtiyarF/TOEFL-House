<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Certificate Request
 *
 * Validates certificate creation data.
 */
class StoreCertificateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Modules\Academic\Models\Certificate::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'uuid', 'exists:students,id'],
            'program_id' => ['required', 'uuid', 'exists:programs,id'],
            'branch_id' => ['required', 'uuid', 'exists:branches,id'],
            'certificate_type' => ['required', Rule::in(['completion', 'achievement', 'participation', 'excellence', 'custom'])],
            'certificate_number' => ['required', 'string', 'max:50', 'unique:certificates,certificate_number'],
            'issue_date' => ['required', 'date', 'before_or_equal:today'],
            'expiry_date' => ['nullable', 'date', 'after:issue_date'],
            'grade' => ['nullable', 'string', 'max:10'],
            'honors' => ['nullable', 'string', 'max:255'],
            'custom_text' => ['nullable', 'string', 'max:2000'],
            'template_id' => ['nullable', 'uuid', 'exists:certificate_templates,id'],
            'issued_by' => ['nullable', 'uuid', 'exists:users,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'student_id.exists' => 'Selected student does not exist.',
            'program_id.exists' => 'Selected program does not exist.',
            'branch_id.exists' => 'Selected branch does not exist.',
            'certificate_type.in' => 'Invalid certificate type selected.',
            'certificate_number.unique' => 'A certificate with this number already exists.',
            'issue_date.before_or_equal' => 'Issue date cannot be in the future.',
            'expiry_date.after' => 'Expiry date must be after issue date.',
            'template_id.exists' => 'Selected template does not exist.',
            'issued_by.exists' => 'Selected issuer does not exist.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Check if student has completed the program
            if ($this->student_id && $this->program_id) {
                $enrollment = \App\Modules\Academic\Models\Enrollment::where('student_id', $this->student_id)
                    ->where('program_id', $this->program_id)
                    ->where('status', 'completed')
                    ->first();

                if (!$enrollment && $this->certificate_type === 'completion') {
                    $validator->errors()->add('student_id', 'Student has not completed the selected program.');
                }
            }

            // Check if student belongs to the branch
            if ($this->student_id && $this->branch_id) {
                $student = \App\Modules\Academic\Models\Student::find($this->student_id);
                
                if ($student && $student->branch_id !== $this->branch_id) {
                    $validator->errors()->add('student_id', 'Student does not belong to the selected branch.');
                }
            }

            // Validate grade format
            if ($this->grade) {
                $validGrades = ['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D', 'F', 'Pass', 'Fail', 'Distinction', 'Merit', 'Credit'];
                
                if (!in_array($this->grade, $validGrades) && !preg_match('/^\d+(\.\d+)?$/', $this->grade)) {
                    $validator->errors()->add('grade', 'Invalid grade format.');
                }
            }

            // Check if student already has a certificate for this program
            if ($this->student_id && $this->program_id) {
                $existingCertificate = \App\Modules\Academic\Models\Certificate::where('student_id', $this->student_id)
                    ->where('program_id', $this->program_id)
                    ->where('certificate_type', $this->certificate_type)
                    ->exists();

                if ($existingCertificate) {
                    $validator->errors()->add('student_id', 'Student already has a certificate of this type for the selected program.');
                }
            }
        });
    }
}

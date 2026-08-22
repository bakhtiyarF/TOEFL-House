<?php

namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class StudentSemester extends Model
{
    use HasUuids;

    protected $fillable = [
        'student_id', 'semester_name', 'class_id',
        'enroll_date', 'fee_amount', 'status',
    ];

    protected $casts = [
        'enroll_date' => 'date',
        'fee_amount' => 'decimal:2',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function academicClass()
    {
        return $this->belongsTo(AcademicClass::class, 'class_id');
    }
}

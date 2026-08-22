<?php

namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicClass extends Model
{
    use HasUuids;

    protected $table = 'classes';

    protected $fillable = [
        'name', 'teacher_id', 'program_id', 'level_id', 'level',
        'capacity', 'min_viable_size', 'schedule_time', 'start_date',
        'end_date', 'status', 'fee', 'gender_policy', 'room_id',
        'time_slot_id', 'academic_term_id', 'activation_date',
        'merged_into_id', 'notes', 'branch_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'activation_date' => 'date',
        'fee' => 'decimal:2',
    ];

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class, 'class_id');
    }

    public function semesters(): HasMany
    {
        return $this->hasMany(StudentSemester::class);
    }

    public function teacherSkills(): HasMany
    {
        return $this->hasMany(ClassTeacherSkill::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class, 'merged_into_id');
    }

    public function activeEnrolledCount(): int
    {
        return $this->semesters()->where('status', 'active')->count();
    }
}

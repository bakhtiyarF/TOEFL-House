<?php

namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * AcademicClass Model
 *
 * Represents a class/course offering with sessions, roster, and teacher assignments.
 */
class AcademicClass extends Model
{
    use HasUuids;

    protected $table = 'classes';

    protected $fillable = [
        'name',
        'teacher_id',
        'program_id',
        'level_id',
        'level',
        'capacity',
        'min_viable_size',
        'schedule_time',
        'start_date',
        'end_date',
        'status',
        'fee',
        'gender_policy',
        'room_id',
        'time_slot_id',
        'academic_term_id',
        'activation_date',
        'merged_into_id',
        'notes',
        'branch_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'activation_date' => 'date',
        'fee' => 'decimal:2',
    ];

    protected $attributes = [
        'status' => 'active',
        'capacity' => 20,
        'min_viable_size' => 5,
        'gender_policy' => 'mixed',
    ];

    // ── Relationships ──

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Iam\Models\Branch::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\PeopleHr\Models\Teacher::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class, 'class_id')->orderByDesc('date');
    }

    public function completedSessions(): HasMany
    {
        return $this->sessions()->where('status', 'completed');
    }

    public function scheduledSessions(): HasMany
    {
        return $this->sessions()->where('status', 'scheduled');
    }

    public function semesters(): HasMany
    {
        return $this->hasMany(StudentSemester::class, 'class_id');
    }

    public function activeSemesters(): HasMany
    {
        return $this->semesters()->where('status', 'active');
    }

    public function students(): HasManyThrough
    {
        return $this->hasManyThrough(
            Student::class,
            StudentSemester::class,
            'class_id',
            'id',
            'id',
            'student_id'
        )->where('student_semesters.status', 'active');
    }

    public function teacherSkills(): HasMany
    {
        return $this->hasMany(ClassTeacherSkill::class, 'class_id');
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class, 'class_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'class_id');
    }

    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class, 'merged_into_id');
    }

    public function mergedClasses(): HasMany
    {
        return $this->hasMany(AcademicClass::class, 'merged_into_id');
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByBranch($query, string $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByTeacher($query, string $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    // ── Accessors ──

    public function getEnrolledCountAttribute(): int
    {
        return $this->activeSemesters()->count();
    }

    public function getFillPercentAttribute(): float
    {
        return $this->capacity > 0
            ? round(($this->enrolled_count / $this->capacity) * 100, 1)
            : 0;
    }

    public function isBelowMinimumSize(): bool
    {
        return $this->enrolled_count < $this->min_viable_size;
    }

    public function isFull(): bool
    {
        return $this->enrolled_count >= $this->capacity;
    }

    public function getAttendanceRateAttribute(): ?float
    {
        $sessions = $this->completedSessions;
        if ($sessions->isEmpty()) return null;

        $totalRosters = 0;
        $presentCount = 0;

        foreach ($sessions as $session) {
            $rosters = $session->rosters;
            $totalRosters += $rosters->count();
            $presentCount += $rosters->where('attendance_status', 'present')->count();
        }

        return $totalRosters > 0 ? round(($presentCount / $totalRosters) * 100, 1) : null;
    }
}

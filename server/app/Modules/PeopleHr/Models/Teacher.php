<?php

namespace App\Modules\PeopleHr\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Teacher Model
 *
 * Represents a teacher in the educational institute.
 * Includes comprehensive relationships for classes, salary, evaluations, etc.
 */
class Teacher extends Model
{
    use HasUuids;

    protected $fillable = [
        'full_name',
        'phone',
        'email',
        'base_salary',
        'salary_type',
        'performance_score',
        'status',
        'branch_id',
        'joined_date',
        'specialization',
        'qualification',
        'contract_type',
        'user_id',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'performance_score' => 'decimal:2',
        'joined_date' => 'date',
    ];

    protected $attributes = [
        'status' => 'active',
        'salary_type' => 'fixed',
        'base_salary' => 0,
        'performance_score' => 0,
    ];

    // ── Relationships ──

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Iam\Models\Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Iam\Models\User::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(\App\Modules\Academic\Models\AcademicClass::class, 'teacher_id');
    }

    public function activeClasses(): HasMany
    {
        return $this->classes()->where('status', 'active');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(\App\Modules\Academic\Models\Session::class, 'teacher_id');
    }

    public function completedSessions(): HasMany
    {
        return $this->sessions()->where('status', 'completed');
    }

    public function classTeacherSkills(): HasMany
    {
        return $this->hasMany(\App\Modules\Academic\Models\ClassTeacherSkill::class, 'teacher_id');
    }

    public function salaryLedger(): HasMany
    {
        return $this->hasMany(\App\Modules\FinancePayroll\Models\TeacherSalaryLedger::class, 'teacher_id');
    }

    public function levelSkillRates(): HasMany
    {
        return $this->hasMany(\App\Modules\FinancePayroll\Models\TeacherLevelSkillRate::class, 'teacher_id');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(TeacherEvaluation::class, 'teacher_id')->orderByDesc('date');
    }

    public function students(): HasManyThrough
    {
        return $this->hasManyThrough(
            \App\Modules\Academic\Models\Student::class,
            \App\Modules\Academic\Models\StudentSemester::class,
            'class_id',
            'id',
            'id',
            'student_id'
        )->where('student_semesters.status', 'active')
         ->whereIn('classes.teacher_id', [$this->id]);
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

    public function scopeBySalaryType($query, string $type)
    {
        return $query->where('salary_type', $type);
    }

    // ── Accessors ──

    public function getTotalStudentsAttribute(): int
    {
        return $this->activeClasses->sum('enrolled_count');
    }

    public function getActiveClassCountAttribute(): int
    {
        return $this->activeClasses()->count();
    }

    public function getLatestEvaluationAttribute(): ?TeacherEvaluation
    {
        return $this->evaluations()->latest('date')->first();
    }

    public function getCurrentPeriodDueAmountAttribute(): float
    {
        $periodKey = now()->format('Y-m');
        $ledger = $this->salaryLedger()
            ->where('period_key', $periodKey)
            ->first();

        return $ledger ? (float)$ledger->due_amount - (float)$ledger->paid_amount : 0;
    }

    public function isFullyPaidForCurrentPeriod(): bool
    {
        return $this->current_period_due_amount <= 0;
    }

    // ── Helpers ──

    public function getSalaryTypeLabelAttribute(): string
    {
        return match($this->salary_type) {
            'fixed' => 'Fixed Salary',
            'per_skill' => 'Per Skill',
            'per_session' => 'Per Session',
            'hybrid' => 'Hybrid (Base + Skills)',
            'per_level' => 'Per Level',
            default => ucfirst($this->salary_type),
        };
    }
}

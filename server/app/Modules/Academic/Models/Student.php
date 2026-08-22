<?php

namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    use HasUuids;

    protected $fillable = [
        'student_code', 'full_name', 'phone', 'email', 'qr_code',
        'status', 'registration_date', 'discount_percent', 'lead_id',
        'gender', 'father_name', 'address_region', 'tazkira_no', 'whatsapp',
        'dob', 'school_or_university', 'emergency_contact_name',
        'emergency_contact_phone', 'placement_score', 'installment_plan',
        'card_design', 'branch_id',
    ];

    protected $casts = [
        'registration_date' => 'date',
        'dob' => 'date',
        'placement_score' => 'json',
        'discount_percent' => 'decimal:2',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Iam\Models\Branch::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function semesters(): HasMany
    {
        return $this->hasMany(StudentSemester::class);
    }

    public function examResults(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }

    public function journeyEvents(): HasMany
    {
        return $this->hasMany(StudentJourneyEvent::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }
}

<?php

namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Student Model
 *
 * Represents a student enrolled in the educational institute.
 * Includes comprehensive relationships for enrollments, payments, journey events, etc.
 */
class Student extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'student_code',
        'full_name',
        'phone',
        'email',
        'gender',
        'father_name',
        'address_region',
        'tazkira_no',
        'whatsapp',
        'dob',
        'school_or_university',
        'emergency_contact_name',
        'emergency_contact_phone',
        'placement_score',
        'lead_id',
        'status',
        'registration_date',
        'discount_percent',
        'branch_id',
    ];

    protected $casts = [
        'placement_score' => 'json',
        'dob' => 'date',
        'registration_date' => 'date',
        'discount_percent' => 'decimal:2',
    ];

    protected $attributes = [
        'status' => 'active',
        'discount_percent' => 0,
    ];

    // ── Relationships ──

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Iam\Models\Branch::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\CrmEnrollment\Models\Visitor::class, 'lead_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function activeEnrollments(): HasMany
    {
        return $this->enrollments()->where('status', 'active');
    }

    public function currentEnrollment(): HasOne
    {
        return $this->hasOne(Enrollment::class)->where('status', 'active')->latestOfMany('started_at');
    }

    public function semesters(): HasMany
    {
        return $this->hasMany(StudentSemester::class);
    }

    public function activeSemesters(): HasMany
    {
        return $this->semesters()->where('status', 'active');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(\App\Modules\FinancePayroll\Models\Payment::class);
    }

    public function completedPayments(): HasMany
    {
        return $this->payments()->where('status', 'completed');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(\App\Modules\FinancePayroll\Models\Invoice::class);
    }

    public function examResults(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function journeyEvents(): HasMany
    {
        return $this->hasMany(StudentJourneyEvent::class)->orderByDesc('occurred_at');
    }

    public function rosters(): HasMany
    {
        return $this->hasMany(Roster::class);
    }

    public function scholarshipAwards(): HasMany
    {
        return $this->hasMany(\App\Modules\FundingImpact\Models\ScholarshipAward::class);
    }

    public function sponsorshipAgreements(): HasMany
    {
        return $this->hasMany(\App\Modules\FundingImpact\Models\SponsorshipAgreement::class);
    }

    public function successStories(): HasMany
    {
        return $this->hasMany(\App\Modules\FundingImpact\Models\SuccessStory::class);
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeGraduated($query)
    {
        return $query->where('status', 'graduated');
    }

    public function scopeByBranch($query, string $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('full_name', 'like', "%{$search}%")
              ->orWhere('student_code', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('tazkira_no', 'like', "%{$search}%");
        });
    }

    // ── Accessors ──

    public function getTotalPaidAttribute(): float
    {
        return $this->completedPayments()->sum('amount');
    }

    public function getTotalDueAttribute(): float
    {
        $enrollments = $this->enrollments->map(function ($enrollment) {
            $snapshot = $enrollment->fee_snapshot_json;
            return $snapshot['net_tuition'] ?? $snapshot['gross_tuition'] ?? 0;
        })->sum();

        return max(0, $enrollments - $this->total_paid);
    }

    public function isFullyPaid(): bool
    {
        return $this->total_due <= 0;
    }

    public function getAttendanceRateAttribute(): float
    {
        $rosters = $this->rosters;
        $total = $rosters->count();
        
        if ($total === 0) return 0;

        $present = $rosters->where('attendance_status', 'present')->count();
        return round(($present / $total) * 100, 1);
    }

    // ── Helpers ──

    public function addJourneyEvent(string $eventType, array $payload = [], ?string $actorUserId = null, ?string $actorName = null): StudentJourneyEvent
    {
        return $this->journeyEvents()->create([
            'event_type' => $eventType,
            'occurred_at' => now(),
            'payload' => $payload,
            'actor_user_id' => $actorUserId,
            'actor_name' => $actorName,
        ]);
    }
}

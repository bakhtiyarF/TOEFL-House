<?php

namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Enrollment Model
 *
 * Represents a student's enrollment in a program/class.
 * Implements copy-on-write pattern for fee snapshots.
 */
class Enrollment extends Model
{
    use HasUuids;

    protected $fillable = [
        'student_id',
        'program_id',
        'program_name',
        'semester_name',
        'level_code',
        'class_id',
        'program_version_id',
        'fee_snapshot_json',
        'enrollment_type',
        'status',
        'skills_focus',
        'discount_percent',
        'scholarship_percent',
        'started_at',
        'ended_at',
        'branch_id',
    ];

    protected $casts = [
        'fee_snapshot_json' => 'json',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'discount_percent' => 'decimal:2',
        'scholarship_percent' => 'decimal:2',
    ];

    protected $attributes = [
        'status' => 'active',
        'enrollment_type' => 'new',
        'discount_percent' => 0,
        'scholarship_percent' => 0,
    ];

    // ── Relationships ──

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function programVersion(): BelongsTo
    {
        return $this->belongsTo(ProgramVersion::class);
    }

    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class, 'class_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Iam\Models\Branch::class);
    }

    public function journeyEvents(): HasMany
    {
        return $this->hasMany(StudentJourneyEvent::class, 'enrollment_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(\App\Modules\FinancePayroll\Models\Payment::class, 'student_id', 'student_id');
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByBranch($query, string $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByStudent($query, string $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeByProgram($query, string $programId)
    {
        return $query->where('program_id', $programId);
    }

    public function scopeByClass($query, string $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    // ── Accessors ──

    public function getGrossTuitionAttribute(): float
    {
        $snapshot = $this->fee_snapshot_json;
        return $snapshot['gross_tuition'] ?? $this->academicClass->fee ?? 0;
    }

    public function getNetTuitionAttribute(): float
    {
        $snapshot = $this->fee_snapshot_json;
        return $snapshot['net_tuition'] ?? $this->gross_tuition;
    }

    public function getTotalPaidAttribute(): float
    {
        return $this->payments()
            ->completed()
            ->sum('amount');
    }

    public function getRemainingBalanceAttribute(): float
    {
        return max(0, $this->net_tuition - $this->total_paid);
    }

    public function isFullyPaid(): bool
    {
        return $this->remaining_balance <= 0;
    }

    public function getDurationInDaysAttribute(): ?int
    {
        if (!$this->started_at) return null;
        $end = $this->ended_at ?? now();
        return $this->started_at->diffInDays($end);
    }

    // ── Helpers ──

    /**
     * Create enrollment with fee snapshot (copy-on-write)
     */
    public static function createWithSnapshot(array $attributes): self
    {
        $programVersionId = $attributes['program_version_id'] ?? null;
        $levelId = $attributes['level_id'] ?? null;
        $branchId = $attributes['branch_id'];

        // Get current fee rules
        $feeRules = [];
        if ($programVersionId) {
            $feeRules = \App\Modules\Academic\Models\FeeRule::where('program_version_id', $programVersionId)
                ->when($levelId, fn($q) => $q->where('level_id', $levelId))
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->get()
                ->toArray();
        }

        // Calculate gross tuition
        $grossTuition = collect($feeRules)
            ->where('fee_type', 'semester')
            ->sum('amount');

        // If no fee rules found, use class fee
        if ($grossTuition == 0 && isset($attributes['class_id'])) {
            $class = AcademicClass::find($attributes['class_id']);
            $grossTuition = $class ? (float)$class->fee : 0;
        }

        // Apply discount and scholarship
        $discountPercent = $attributes['discount_percent'] ?? 0;
        $scholarshipPercent = $attributes['scholarship_percent'] ?? 0;

        $discountAmount = $grossTuition * ($discountPercent / 100);
        $scholarshipAmount = $grossTuition * ($scholarshipPercent / 100);
        $netTuition = max(0, $grossTuition - $discountAmount - $scholarshipAmount);

        // Create fee snapshot
        $attributes['fee_snapshot_json'] = [
            'snapshot_at' => now()->toIso8601String(),
            'program_version_id' => $programVersionId,
            'gross_tuition' => $grossTuition,
            'discount_percent' => $discountPercent,
            'scholarship_percent' => $scholarshipPercent,
            'discount_amount' => $discountAmount,
            'scholarship_amount' => $scholarshipAmount,
            'net_tuition' => $netTuition,
            'fee_rules' => $feeRules,
        ];

        $attributes['started_at'] = $attributes['started_at'] ?? now();

        return static::create($attributes);
    }

    /**
     * Update fee snapshot (e.g., when discount/scholarship changes)
     */
    public function updateFeeSnapshot(float $discountPercent = null, float $scholarshipPercent = null): void
    {
        $snapshot = $this->fee_snapshot_json ?? [];
        $grossTuition = $snapshot['gross_tuition'] ?? $this->gross_tuition;

        $discountPercent = $discountPercent ?? $this->discount_percent;
        $scholarshipPercent = $scholarshipPercent ?? $this->scholarship_percent;

        $discountAmount = $grossTuition * ($discountPercent / 100);
        $scholarshipAmount = $grossTuition * ($scholarshipPercent / 100);
        $netTuition = max(0, $grossTuition - $discountAmount - $scholarshipAmount);

        $snapshot['discount_percent'] = $discountPercent;
        $snapshot['scholarship_percent'] = $scholarshipPercent;
        $snapshot['discount_amount'] = $discountAmount;
        $snapshot['scholarship_amount'] = $scholarshipAmount;
        $snapshot['net_tuition'] = $netTuition;
        $snapshot['updated_at'] = now()->toIso8601String();

        $this->update([
            'fee_snapshot_json' => $snapshot,
            'discount_percent' => $discountPercent,
            'scholarship_percent' => $scholarshipPercent,
        ]);
    }
}

<?php

namespace App\Modules\FundingImpact\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Scholarship Model
 *
 * Represents a scholarship fund that can be awarded to students.
 * Includes relationships to donor, campaign, and scholarship awards.
 */
class Scholarship extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'description',
        'donor_id',
        'campaign_id',
        'donation_id',
        'total_amount',
        'awarded_amount',
        'remaining_amount',
        'max_award_amount',
        'min_gpa',
        'eligible_programs',
        'eligible_levels',
        'start_date',
        'end_date',
        'status',
        'branch_id',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'awarded_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'max_award_amount' => 'decimal:2',
        'min_gpa' => 'decimal:2',
        'eligible_programs' => 'array',
        'eligible_levels' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected $attributes = [
        'awarded_amount' => 0,
        'status' => 'active',
    ];

    // ── Relationships ──

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Iam\Models\Branch::class);
    }

    public function awards(): HasMany
    {
        return $this->hasMany(ScholarshipAward::class);
    }

    public function activeAwards(): HasMany
    {
        return $this->awards()->where('status', 'active');
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExhausted($query)
    {
        return $query->where('status', 'exhausted');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeByBranch($query, string $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByDonor($query, string $donorId)
    {
        return $query->where('donor_id', $donorId);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'active')
                     ->where('remaining_amount', '>', 0)
                     ->where(function ($q) {
                         $q->whereNull('end_date')
                           ->orWhere('end_date', '>=', now());
                     });
    }

    // ── Accessors ──

    public function getUtilizationPercentAttribute(): float
    {
        return $this->total_amount > 0
            ? round(($this->awarded_amount / $this->total_amount) * 100, 2)
            : 0;
    }

    public function getAwardCountAttribute(): int
    {
        return $this->awards()->count();
    }

    public function getActiveAwardCountAttribute(): int
    {
        return $this->activeAwards()->count();
    }

    public function getAverageAwardAmountAttribute(): float
    {
        $count = $this->award_count;
        return $count > 0 ? round($this->awarded_amount / $count, 2) : 0;
    }

    public function isAvailable(): bool
    {
        return $this->status === 'active'
            && $this->remaining_amount > 0
            && (!$this->end_date || $this->end_date->isFuture());
    }

    public function isExhausted(): bool
    {
        return $this->remaining_amount <= 0;
    }

    public function isExpired(): bool
    {
        return $this->end_date && $this->end_date->isPast();
    }

    // ── Helpers ──

    public function award(float $amount, string $studentId, string $semester = null): ScholarshipAward
    {
        if ($amount > $this->remaining_amount) {
            throw new \Exception('Insufficient scholarship funds');
        }

        if ($this->max_award_amount && $amount > $this->max_award_amount) {
            throw new \Exception('Amount exceeds maximum award limit');
        }

        $award = $this->awards()->create([
            'student_id' => $studentId,
            'amount' => $amount,
            'semester' => $semester,
            'awarded_date' => now(),
            'status' => 'active',
            'branch_id' => $this->branch_id,
        ]);

        $this->increment('awarded_amount', $amount);
        $this->update(['remaining_amount' => $this->total_amount - $this->awarded_amount]);

        // Check if scholarship is exhausted
        if ($this->isExhausted()) {
            $this->update(['status' => 'exhausted']);
        }

        return $award;
    }

    public function checkEligibility(\App\Modules\Academic\Models\Student $student): bool
    {
        // Check GPA if required
        if ($this->min_gpa && $student->gpa < $this->min_gpa) {
            return false;
        }

        // Check program eligibility
        if ($this->eligible_programs && !in_array($student->program_id, $this->eligible_programs)) {
            return false;
        }

        // Check level eligibility
        if ($this->eligible_levels && !in_array($student->level, $this->eligible_levels)) {
            return false;
        }

        return true;
    }
}

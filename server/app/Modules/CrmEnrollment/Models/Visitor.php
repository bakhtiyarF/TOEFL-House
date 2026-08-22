<?php

namespace App\Modules\CrmEnrollment\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Visitor Model
 *
 * Represents a potential student (lead) in the CRM system.
 * Includes relationships to campaign, follow-ups, and conversion to student.
 */
class Visitor extends Model
{
    use HasUuids;

    protected $fillable = [
        'serial_no',
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
        'source',
        'campaign_id',
        'stage',
        'status',
        'interested_program',
        'placement_score',
        'placement_test_date',
        'follow_up_date',
        'follow_up_notes',
        'assigned_to',
        'converted_at',
        'student_id',
        'branch_id',
        'notes',
    ];

    protected $casts = [
        'placement_score' => 'json',
        'dob' => 'date',
        'placement_test_date' => 'date',
        'follow_up_date' => 'date',
        'converted_at' => 'datetime',
    ];

    protected $attributes = [
        'stage' => 'lead',
        'status' => 'active',
    ];

    // ── Relationships ──

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Iam\Models\Branch::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(VisitorFollowUp::class)->orderByDesc('follow_up_date');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Iam\Models\User::class, 'assigned_to');
    }

    public function convertedStudent(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Academic\Models\Student::class, 'student_id');
    }

    public function latestFollowUp(): HasOne
    {
        return $this->hasOne(VisitorFollowUp::class)->latestOfMany('follow_up_date');
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeConverted($query)
    {
        return $query->whereNotNull('converted_at');
    }

    public function scopeNotConverted($query)
    {
        return $query->whereNull('converted_at');
    }

    public function scopeByBranch($query, string $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByStage($query, string $stage)
    {
        return $query->where('stage', $stage);
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function scopeByCampaign($query, string $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }

    public function scopeAssignedTo($query, string $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeNeedsFollowUp($query)
    {
        return $query->whereNotNull('follow_up_date')
                     ->where('follow_up_date', '<=', now())
                     ->whereNull('converted_at');
    }

    public function scopePlacementCompleted($query)
    {
        return $query->whereNotNull('placement_score');
    }

    public function scopePlacementPending($query)
    {
        return $query->whereNull('placement_score');
    }

    // ── Accessors ──

    public function isConverted(): bool
    {
        return $this->converted_at !== null;
    }

    public function getPlacementScoreValueAttribute(): ?float
    {
        $score = $this->placement_score;
        return is_array($score) ? ($score['score'] ?? null) : null;
    }

    public function getDaysSinceLastFollowUpAttribute(): ?int
    {
        $latest = $this->latestFollowUp;
        return $latest ? $latest->follow_up_date->diffInDays(now()) : null;
    }

    public function getDaysInPipelineAttribute(): int
    {
        return $this->created_at->diffInDays(now());
    }

    // ── Helpers ──

    public function convert(\App\Modules\Academic\Models\Student $student): void
    {
        $this->update([
            'student_id' => $student->id,
            'converted_at' => now(),
            'stage' => 'converted',
        ]);
    }

    public function advanceStage(): void
    {
        $stages = ['lead', 'contacted', 'interested', 'placement_test', 'placement_completed', 'enrollment', 'converted'];
        $currentIndex = array_search($this->stage, $stages);

        if ($currentIndex !== false && $currentIndex < count($stages) - 1) {
            $this->update([
                'stage' => $stages[$currentIndex + 1],
            ]);
        }
    }

    public function addFollowUp(array $data): VisitorFollowUp
    {
        return $this->followUps()->create($data);
    }

    public static function generateSerialNumber(): string
    {
        $year = now()->format('Y');
        $lastVisitor = static::whereYear('created_at', $year)
            ->orderByDesc('created_at')
            ->first();

        $sequence = $lastVisitor
            ? (int)substr($lastVisitor->serial_no, -6) + 1
            : 1;

        return sprintf('VIS-%s-%06d', $year, $sequence);
    }
}

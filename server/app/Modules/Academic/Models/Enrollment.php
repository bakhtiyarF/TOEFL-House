<?php

namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Enrollment — the copy-on-write pin
 *
 * Every enrollment freezes its program version and fee snapshot at creation time.
 * After this point, changing the program version or fee rules MUST NEVER alter
 * this enrollment's stored snapshot. (02 §4, 05 §6)
 */
class Enrollment extends Model
{
    use HasUuids;

    protected $fillable = [
        'student_id', 'program_id', 'program_name', 'semester_name',
        'level_code', 'class_id', 'program_version_id', 'fee_snapshot_json',
        'enrollment_type', 'status', 'skills_focus', 'discount_percent',
        'scholarship_percent', 'started_at', 'ended_at', 'branch_id',
    ];

    protected $casts = [
        'fee_snapshot_json' => 'json',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'discount_percent' => 'decimal:2',
        'scholarship_percent' => 'decimal:2',
    ];

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

    /**
     * Create enrollment with the copy-on-write pin (05 §6)
     * Snapshots fee rules at creation time — never re-reads the live catalog
     */
    public static function createWithSnapshot(array $attributes): self
    {
        $programVersionId = $attributes['program_version_id'];
        $levelId = $attributes['level_id'] ?? null;
        $branchId = $attributes['branch_id'];

        // Snapshot current fee rules for this version/level/branch
        $feeRules = \Illuminate\Support\Facades\DB::table('fee_rules')
            ->where(function ($q) use ($programVersionId) {
                $q->where('program_version_id', $programVersionId)->orWhereNull('program_version_id');
            })
            ->where(function ($q) use ($levelId) {
                if ($levelId) {
                    $q->where('level_id', $levelId)->orWhereNull('level_id');
                } else {
                    $q->whereNull('level_id');
                }
            })
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)->orWhereNull('branch_id');
            })
            ->get()
            ->toArray();

        $attributes['fee_snapshot_json'] = json_encode([
            'snapshot_at' => now()->toIso8601String(),
            'program_version_id' => $programVersionId,
            'fee_rules' => $feeRules,
        ]);

        $attributes['started_at'] = $attributes['started_at'] ?? now();

        return self::create($attributes);
    }
}

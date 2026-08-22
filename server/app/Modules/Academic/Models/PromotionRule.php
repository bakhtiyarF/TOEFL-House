<?php
namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PromotionRule extends Model
{
    use HasUuids;

    protected $fillable = [
        'program_version_id', 'from_level_id', 'to_level_id', 'name',
        'min_score', 'min_attendance_pct', 'require_all_subjects',
        'auto_promote', 'branch_id', 'version',
    ];

    protected $casts = [
        'require_all_subjects' => 'boolean',
        'auto_promote' => 'boolean',
    ];
}

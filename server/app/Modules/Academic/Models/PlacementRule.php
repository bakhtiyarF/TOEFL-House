<?php
namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PlacementRule extends Model
{
    use HasUuids;

    protected $fillable = [
        'program_version_id', 'name', 'min_score', 'max_score',
        'recommended_level_id', 'branch_id', 'sort_order', 'version',
    ];

    public function recommendedLevel()
    {
        return $this->belongsTo(Level::class, 'recommended_level_id');
    }
}

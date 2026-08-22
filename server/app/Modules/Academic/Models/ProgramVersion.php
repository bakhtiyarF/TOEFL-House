<?php

namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramVersion extends Model
{
    use HasUuids;

    protected $fillable = [
        'program_id', 'version_label', 'version_number', 'status',
        'effective_from', 'effective_to', 'is_default', 'published_at',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'published_at' => 'datetime',
        'is_default' => 'boolean',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function levels(): HasMany
    {
        return $this->hasMany(Level::class);
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    public function promotionRules(): HasMany
    {
        return $this->hasMany(PromotionRule::class);
    }

    public function placementRules(): HasMany
    {
        return $this->hasMany(PlacementRule::class);
    }
}

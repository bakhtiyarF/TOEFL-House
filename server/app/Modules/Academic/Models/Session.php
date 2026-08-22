<?php

namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Session extends Model
{
    use HasUuids;

    protected $fillable = [
        'class_id', 'date', 'start_time', 'end_time',
        'topic', 'status', 'teacher_id',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class, 'class_id');
    }

    public function rosters(): HasMany
    {
        return $this->hasMany(Roster::class);
    }

    public function homework(): HasMany
    {
        return $this->hasMany(Homework::class);
    }
}

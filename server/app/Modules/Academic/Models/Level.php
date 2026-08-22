<?php

namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
    use HasUuids;

    protected $fillable = [
        'program_id', 'program_version_id', 'name', 'code',
        'order', 'duration_months', 'default_fee', 'pass_mark', 'min_viable_size',
    ];

    protected $casts = [
        'default_fee' => 'decimal:2',
    ];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function programVersion()
    {
        return $this->belongsTo(ProgramVersion::class);
    }
}

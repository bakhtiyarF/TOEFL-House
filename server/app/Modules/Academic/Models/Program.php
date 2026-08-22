<?php

namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'description', 'duration_months', 'code', 'is_active', 'branch_id'];

    protected $casts = ['is_active' => 'boolean'];

    public function versions()
    {
        return $this->hasMany(ProgramVersion::class);
    }

    public function levels()
    {
        return $this->hasMany(Level::class);
    }
}

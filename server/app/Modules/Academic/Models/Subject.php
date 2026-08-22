<?php
namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasUuids;

    protected $fillable = ['program_version_id', 'level_id', 'code', 'name', 'hours', 'sort_order'];
}

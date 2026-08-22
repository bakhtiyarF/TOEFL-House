<?php
namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ClassTeacherSkill extends Model
{
    use HasUuids;

    protected $fillable = ['class_id', 'teacher_id', 'skill_id', 'monthly_rate'];

    protected $casts = ['monthly_rate' => 'decimal:2'];
}

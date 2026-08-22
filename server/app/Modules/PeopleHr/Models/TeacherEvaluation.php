<?php
namespace App\Modules\PeopleHr\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TeacherEvaluation extends Model
{
    use HasUuids;

    protected $fillable = ['teacher_id', 'evaluator_id', 'date', 'score', 'criteria', 'notes'];

    protected $casts = ['date' => 'date', 'score' => 'decimal:2', 'criteria' => 'json'];
}

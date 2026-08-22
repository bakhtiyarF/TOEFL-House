<?php
namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasUuids;

    protected $fillable = ['title', 'date', 'fee', 'class_id', 'type', 'branch_id'];

    protected $casts = ['date' => 'date', 'fee' => 'decimal:2'];
}

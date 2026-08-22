<?php
namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Homework extends Model
{
    use HasUuids;

    protected $table = 'homework';

    protected $fillable = ['session_id', 'title', 'description', 'due_date', 'assigned_by'];

    protected $casts = ['due_date' => 'date'];
}

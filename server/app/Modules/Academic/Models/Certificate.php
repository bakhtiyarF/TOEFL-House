<?php
namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use HasUuids;

    protected $fillable = [
        'student_id', 'program_id', 'level_id', 'issue_date',
        'certificate_no', 'grade', 'branch_id',
    ];

    protected $casts = ['issue_date' => 'date'];
}

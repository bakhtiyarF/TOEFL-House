<?php

namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Roster extends Model
{
    use HasUuids;

    protected $fillable = [
        'session_id', 'student_id', 'attendance_status', 'marked_at',
    ];

    protected $casts = [
        'marked_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}

<?php
namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ExamResult extends Model
{
    use HasUuids;

    protected $fillable = [
        'exam_id', 'student_id', 'score', 'status',
        'exam_fee_paid', 'certificate_issued', 'certificate_no',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'exam_fee_paid' => 'boolean',
        'certificate_issued' => 'boolean',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}

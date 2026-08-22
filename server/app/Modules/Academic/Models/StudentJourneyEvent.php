<?php

namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class StudentJourneyEvent extends Model
{
    use HasUuids;

    protected $table = 'student_journey_events';

    protected $fillable = [
        'student_id', 'event_type', 'occurred_at', 'enrollment_id',
        'payload', 'actor_user_id', 'actor_name', 'correlation_id',
        'causation_id', 'schema_version',
    ];

    protected $casts = [
        'payload' => 'json',
        'occurred_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Event vocabulary constants (02 §9.1)
     */
    public const STUDENT_REGISTERED = 'STUDENT_REGISTERED';
    public const PLACEMENT_TEST_RECORDED = 'PLACEMENT_TEST_RECORDED';
    public const PLACEMENT_PASSED = 'PLACEMENT_PASSED';
    public const PLACEMENT_FAILED = 'PLACEMENT_FAILED';
    public const ENROLLMENT_CREATED = 'ENROLLMENT_CREATED';
    public const ENROLLMENT_STATUS_CHANGED = 'ENROLLMENT_STATUS_CHANGED';
    public const CLASS_ASSIGNED = 'CLASS_ASSIGNED';
    public const INVOICE_ISSUED = 'INVOICE_ISSUED';
    public const PAYMENT_RECORDED = 'PAYMENT_RECORDED';
    public const ID_CARD_ISSUED = 'ID_CARD_ISSUED';
    public const BOOK_ISSUED = 'BOOK_ISSUED';
    public const ATTENDANCE_RECORDED = 'ATTENDANCE_RECORDED';
    public const EXAM_RESULT_RECORDED = 'EXAM_RESULT_RECORDED';
    public const PROMOTION_DECIDED = 'PROMOTION_DECIDED';
    public const STATUS_CHANGED = 'STATUS_CHANGED';
    public const GRADUATED = 'GRADUATED';
    public const NOTE_ADDED = 'NOTE_ADDED';

    /** Financial events (02 §9.1) */
    public const FINANCIAL_EVENTS = [
        self::INVOICE_ISSUED,
        self::PAYMENT_RECORDED,
    ];
}

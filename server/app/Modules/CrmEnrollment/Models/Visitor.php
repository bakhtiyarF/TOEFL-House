<?php
namespace App\Modules\CrmEnrollment\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Visitor extends Model
{
    use HasUuids;

    protected $fillable = [
        'serial_no', 'full_name', 'phone', 'email', 'gender', 'source',
        'campaign_id', 'stage', 'assigned_to', 'visit_date', 'status', 'notes',
        'branch_id', 'interested_course', 'follow_up_status', 'next_contact_date',
        'father_name', 'address_region', 'tazkira_no', 'whatsapp', 'dob',
        'school_or_university', 'emergency_contact_name', 'emergency_contact_phone',
        'placement_score',
    ];

    protected $casts = ['placement_score' => 'json', 'visit_date' => 'date', 'dob' => 'date', 'next_contact_date' => 'date'];

    public function followups(): HasMany { return $this->hasMany(VisitorFollowup::class); }

    public function isConversionReady(): bool
    {
        return in_array($this->stage, ['placement_completed', 'registration', 'enrollment']);
    }
}

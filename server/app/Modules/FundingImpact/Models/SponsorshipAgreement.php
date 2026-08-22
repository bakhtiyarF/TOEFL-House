<?php
namespace App\Modules\FundingImpact\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SponsorshipAgreement extends Model
{
    use HasUuids;

    protected $fillable = [
        'donor_id', 'student_id', 'program_id', 'monthly_amount',
        'start_date', 'end_date', 'status', 'branch_id',
    ];

    protected $casts = ['monthly_amount' => 'decimal:2', 'start_date' => 'date', 'end_date' => 'date'];
}

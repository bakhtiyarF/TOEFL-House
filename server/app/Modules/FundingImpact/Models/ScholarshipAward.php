<?php
namespace App\Modules\FundingImpact\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ScholarshipAward extends Model
{
    use HasUuids;

    protected $fillable = ['scholarship_id', 'student_id', 'amount', 'award_date', 'semester', 'notes', 'branch_id'];

    protected $casts = ['amount' => 'decimal:2', 'award_date' => 'date'];
}

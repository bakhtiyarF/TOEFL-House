<?php
namespace App\Modules\FundingImpact\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Scholarship extends Model
{
    use HasUuids;

    protected $fillable = [
        'name', 'donor_id', 'campaign_id', 'total_budget', 'allocated_amount',
        'criteria', 'status', 'branch_id',
    ];

    protected $casts = ['total_budget' => 'decimal:2', 'allocated_amount' => 'decimal:2'];

    public function awards(): HasMany { return $this->hasMany(ScholarshipAward::class); }

    public function remainingBudget(): float { return $this->total_budget - $this->allocated_amount; }
    public function utilizationPercent(): float { return $this->total_budget > 0 ? round(($this->allocated_amount / $this->total_budget) * 100, 1) : 0; }
}

<?php
namespace App\Modules\FundingImpact\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FundingCampaign extends Model
{
    use HasUuids;

    protected $fillable = [
        'name', 'description', 'donor_id', 'target_amount', 'raised_amount',
        'start_date', 'end_date', 'status', 'branch_id',
    ];

    protected $casts = ['target_amount' => 'decimal:2', 'raised_amount' => 'decimal:2', 'start_date' => 'date', 'end_date' => 'date'];

    public function progressPercent(): float { return $this->target_amount > 0 ? min(100, round(($this->raised_amount / $this->target_amount) * 100, 1)) : 0; }
}

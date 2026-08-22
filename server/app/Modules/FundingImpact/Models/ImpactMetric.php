<?php
namespace App\Modules\FundingImpact\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ImpactMetric extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'category', 'target_value', 'current_value', 'period', 'branch_id'];

    protected $casts = ['target_value' => 'decimal:2', 'current_value' => 'decimal:2'];

    public function progressPercent(): float { return $this->target_value > 0 ? round(($this->current_value / $this->target_value) * 100, 1) : 0; }
}

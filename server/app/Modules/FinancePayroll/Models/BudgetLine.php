<?php
namespace App\Modules\FinancePayroll\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class BudgetLine extends Model
{
    use HasUuids;

    protected $fillable = [
        'name', 'current_amount', 'allocated_amount', 'icon',
        'cost_type', 'is_marketing', 'purpose', 'branch_id',
    ];

    protected $casts = ['current_amount' => 'decimal:2', 'allocated_amount' => 'decimal:2', 'is_marketing' => 'boolean'];

    public function utilizationPercent(): float
    {
        return $this->allocated_amount > 0
            ? round(($this->current_amount / $this->allocated_amount) * 100, 1)
            : 0;
    }
}

<?php
namespace App\Modules\FinancePayroll\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FinancialTransaction extends Model
{
    use HasUuids;

    protected $table = 'financial_transactions';

    protected $fillable = [
        'type', 'category', 'amount', 'date', 'description',
        'reference_id', 'operator_name', 'branch_id',
    ];

    protected $casts = ['date' => 'date', 'amount' => 'decimal:2'];
}

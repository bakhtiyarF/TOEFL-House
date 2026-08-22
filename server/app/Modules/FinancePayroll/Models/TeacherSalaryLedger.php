<?php
namespace App\Modules\FinancePayroll\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TeacherSalaryLedger extends Model
{
    use HasUuids;

    protected $table = 'teacher_salary_ledger';

    protected $fillable = [
        'teacher_id', 'period_key', 'period_label', 'due_amount', 'paid_amount',
        'payment_type', 'transaction_id', 'notes', 'branch_id', 'paid_at', 'operator_name',
    ];

    protected $casts = ['due_amount' => 'decimal:2', 'paid_amount' => 'decimal:2', 'paid_at' => 'datetime'];
}

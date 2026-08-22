<?php
namespace App\Modules\FinancePayroll\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasUuids;

    protected $fillable = [
        'student_id', 'total_amount', 'discount_amount', 'net_amount',
        'status', 'issue_date', 'due_date', 'invoice_number', 'issued_by', 'notes', 'branch_id',
    ];

    protected $casts = ['total_amount' => 'decimal:2', 'discount_amount' => 'decimal:2', 'net_amount' => 'decimal:2', 'issue_date' => 'date', 'due_date' => 'date'];

    public function items(): HasMany { return $this->hasMany(InvoiceItem::class); }
}

<?php
namespace App\Modules\FinancePayroll\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasUuids;

    protected $fillable = [
        'student_id', 'invoice_id', 'amount', 'date', 'payment_method',
        'status', 'category', 'notes', 'receipt_number', 'semester', 'branch_id',
    ];

    protected $casts = ['date' => 'date', 'amount' => 'decimal:2'];

    public function student(): BelongsTo { return $this->belongsTo(\App\Modules\Academic\Models\Student::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
}

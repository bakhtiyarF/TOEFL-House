<?php
namespace App\Modules\FinancePayroll\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasUuids;

    protected $fillable = ['invoice_id', 'description', 'quantity', 'unit_price', 'amount'];

    protected $casts = ['unit_price' => 'decimal:2', 'amount' => 'decimal:2'];
}

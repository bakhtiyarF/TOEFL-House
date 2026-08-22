<?php
namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class BookSale extends Model
{
    use HasUuids;

    protected $fillable = [
        'book_id', 'quantity', 'total_amount', 'discount_amount', 'net_amount',
        'payment_method', 'status', 'date', 'customer_name', 'student_id', 'branch_id',
    ];

    protected $casts = ['total_amount' => 'decimal:2', 'discount_amount' => 'decimal:2', 'net_amount' => 'decimal:2', 'date' => 'date'];
}

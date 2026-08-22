<?php
namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class BookRestockHistory extends Model
{
    use HasUuids;

    protected $fillable = ['book_id', 'date', 'quantity', 'price', 'purchase_price'];

    protected $casts = ['date' => 'date', 'price' => 'decimal:2', 'purchase_price' => 'decimal:2'];
}

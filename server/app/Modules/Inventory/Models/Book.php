<?php
namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasUuids;

    protected $fillable = ['title', 'price', 'purchase_price', 'stock', 'is_chapter', 'branch_id', 'entry_date'];

    protected $casts = ['price' => 'decimal:2', 'purchase_price' => 'decimal:2', 'is_chapter' => 'boolean', 'entry_date' => 'date'];

    public function sales(): HasMany { return $this->hasMany(BookSale::class); }
    public function restockHistory(): HasMany { return $this->hasMany(BookRestockHistory::class); }

    public function isOutOfStock(): bool { return $this->stock <= 0; }
    public function isLowStock(): bool { return $this->stock > 0 && $this->stock < 10; }
}

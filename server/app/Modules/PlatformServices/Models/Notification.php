<?php
namespace App\Modules\PlatformServices\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasUuids;

    protected $fillable = ['title', 'message', 'date', 'read', 'type', 'user_id', 'link', 'branch_id'];

    protected $casts = ['date' => 'date', 'read' => 'boolean'];

    public function markAsRead(): void { $this->update(['read' => true]); }
}

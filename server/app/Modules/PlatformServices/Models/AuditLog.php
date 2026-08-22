<?php
namespace App\Modules\PlatformServices\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'operator_id', 'operator_name', 'action', 'date', 'time',
        'old_value', 'new_value', 'ip', 'device', 'branch_id',
    ];

    protected $casts = ['old_value' => 'json', 'new_value' => 'json', 'date' => 'date'];
}

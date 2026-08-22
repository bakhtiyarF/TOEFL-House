<?php
namespace App\Modules\PlatformServices\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class WorkflowInstance extends Model
{
    use HasUuids;

    protected $fillable = [
        'definition_id', 'entity_type', 'entity_id', 'current_step',
        'status', 'branch_id', 'initiated_by', 'started_at', 'completed_at', 'payload',
    ];

    protected $casts = ['payload' => 'json', 'started_at' => 'datetime', 'completed_at' => 'datetime'];

    public function isComplete(): bool { return in_array($this->status, ['approved', 'rejected', 'completed', 'cancelled']); }
}

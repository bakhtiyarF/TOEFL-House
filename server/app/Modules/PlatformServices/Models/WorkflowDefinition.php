<?php
namespace App\Modules\PlatformServices\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowDefinition extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'trigger', 'steps', 'is_active'];

    protected $casts = ['steps' => 'json', 'is_active' => 'boolean'];

    public function instances(): HasMany { return $this->hasMany(WorkflowInstance::class, 'definition_id'); }
}

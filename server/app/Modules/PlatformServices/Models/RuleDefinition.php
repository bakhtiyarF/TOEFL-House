<?php
namespace App\Modules\PlatformServices\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RuleDefinition extends Model
{
    use HasUuids;

    protected $table = 'rule_definitions';

    protected $fillable = [
        'name', 'description', 'category', 'conditions', 'actions',
        'priority', 'is_active', 'scope_branch_id', 'version',
        'last_modified_by', 'last_modified_at',
    ];

    protected $casts = ['conditions' => 'json', 'actions' => 'json', 'is_active' => 'boolean', 'last_modified_at' => 'datetime'];

    public function versions(): HasMany { return $this->hasMany(RuleVersion::class, 'rule_id'); }
    public function evaluationLogs(): HasMany { return $this->hasMany(RuleEvaluationLog::class, 'rule_id'); }
}

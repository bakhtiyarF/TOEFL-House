<?php
namespace App\Modules\PlatformServices\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RuleVersion extends Model
{
    use HasUuids;

    protected $table = 'rule_versions';

    protected $fillable = ['rule_id', 'version', 'conditions', 'actions', 'priority', 'is_active', 'modified_by', 'modified_at'];

    protected $casts = ['conditions' => 'json', 'actions' => 'json', 'is_active' => 'boolean', 'modified_at' => 'datetime'];
}

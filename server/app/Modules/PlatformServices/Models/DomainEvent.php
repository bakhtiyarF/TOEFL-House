<?php
namespace App\Modules\PlatformServices\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DomainEvent extends Model
{
    use HasUuids;

    protected $table = 'domain_events';
    public $timestamps = false;

    protected $fillable = [
        'type', 'aggregate_type', 'aggregate_id', 'payload', 'occurred_at',
        'operator_id', 'branch_id', 'correlation_id', 'causation_id',
        'schema_version', 'published', 'metadata',
    ];

    protected $casts = ['payload' => 'json', 'metadata' => 'json', 'occurred_at' => 'datetime', 'published' => 'boolean'];
}

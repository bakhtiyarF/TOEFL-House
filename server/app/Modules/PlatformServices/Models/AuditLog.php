<?php

namespace App\Modules\PlatformServices\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Audit Log Model
 *
 * Tracks changes to auditable models.
 */
class AuditLog extends Model
{
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'auditable_type',
        'auditable_id',
        'action',
        'old_values',
        'new_values',
        'user_id',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that should be mutated to dates.
     */
    protected $dates = [
        'created_at',
    ];

    /**
     * Get the auditable model.
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Iam\Models\User::class);
    }

    /**
     * Scope a query to only include logs for a specific action.
     */
    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope a query to only include logs for a specific user.
     */
    public function scopeUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include logs for a specific model type.
     */
    public function scopeForModel($query, string $modelType)
    {
        return $query->where('auditable_type', $modelType);
    }

    /**
     * Get a human-readable description of the change.
     */
    public function getDescriptionAttribute(): string
    {
        $userName = $this->user ? $this->user->full_name : 'System';
        $modelName = class_basename($this->auditable_type);

        switch ($this->action) {
            case 'created':
                return "{$userName} created {$modelName} #{$this->auditable_id}";
            
            case 'updated':
                $changedFields = array_keys($this->new_values);
                return "{$userName} updated {$modelName} #{$this->auditable_id} (" . implode(', ', $changedFields) . ")";
            
            case 'deleted':
                return "{$userName} deleted {$modelName} #{$this->auditable_id}";
            
            default:
                return "{$userName} performed {$this->action} on {$modelName} #{$this->auditable_id}";
        }
    }

    /**
     * Get the changes as a formatted array.
     */
    public function getFormattedChangesAttribute(): array
    {
        $changes = [];

        foreach ($this->new_values as $field => $newValue) {
            $oldValue = $this->old_values[$field] ?? null;

            $changes[$field] = [
                'old' => $oldValue,
                'new' => $newValue,
                'changed' => $oldValue !== $newValue,
            ];
        }

        return $changes;
    }
}

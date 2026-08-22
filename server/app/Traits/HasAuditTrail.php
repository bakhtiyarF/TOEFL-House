<?php

namespace App\Traits;

use App\Modules\PlatformServices\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

/**
 * Has Audit Trail Trait
 *
 * Provides audit trail functionality for models.
 */
trait HasAuditTrail
{
    /**
     * Boot the trait.
     */
    protected static function bootHasAuditTrail()
    {
        static::created(function ($model) {
            $model->logAudit('created', $model->toArray(), []);
        });

        static::updated(function ($model) {
            $model->logAudit('updated', $model->getChanges(), $model->getOriginal());
        });

        static::deleted(function ($model) {
            $model->logAudit('deleted', [], $model->toArray());
        });
    }

    /**
     * Log an audit entry.
     */
    protected function logAudit(string $action, array $newValues, array $oldValues)
    {
        // Filter out sensitive fields
        $sensitiveFields = ['password', 'password_confirmation', 'token', 'api_key', 'secret'];
        
        $filteredNew = array_diff_key($newValues, array_flip($sensitiveFields));
        $filteredOld = array_diff_key($oldValues, array_flip($sensitiveFields));

        AuditLog::create([
            'auditable_type' => get_class($this),
            'auditable_id' => $this->id,
            'action' => $action,
            'old_values' => $filteredOld,
            'new_values' => $filteredNew,
            'user_id' => Auth::id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * Get audit logs for this model.
     */
    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable')
            ->orderByDesc('created_at');
    }

    /**
     * Get audit logs for a specific action.
     */
    public function auditLogsForAction(string $action)
    {
        return $this->auditLogs()->where('action', $action);
    }

    /**
     * Get audit logs by user.
     */
    public function auditLogsByUser($userId)
    {
        return $this->auditLogs()->where('user_id', $userId);
    }
}

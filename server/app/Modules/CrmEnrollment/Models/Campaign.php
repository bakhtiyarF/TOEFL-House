<?php

namespace App\Modules\CrmEnrollment\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Campaign Model
 *
 * Represents a marketing campaign for student acquisition.
 * Includes relationships to visitors and conversion tracking.
 */
class Campaign extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'description',
        'type',
        'source',
        'start_date',
        'end_date',
        'budget',
        'spent',
        'target_visitors',
        'target_conversions',
        'status',
        'branch_id',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
        'spent' => 'decimal:2',
    ];

    protected $attributes = [
        'status' => 'draft',
        'spent' => 0,
    ];

    // ── Relationships ──

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Iam\Models\Branch::class);
    }

    public function visitors(): HasMany
    {
        return $this->hasMany(Visitor::class);
    }

    public function convertedVisitors(): HasMany
    {
        return $this->visitors()->whereNotNull('converted_at');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Iam\Models\User::class, 'created_by');
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByBranch($query, string $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function scopeRunning($query)
    {
        return $query->where('status', 'active')
                     ->where('start_date', '<=', now())
                     ->where(function ($q) {
                         $q->whereNull('end_date')
                           ->orWhere('end_date', '>=', now());
                     });
    }

    // ── Accessors ──

    public function getTotalVisitorsAttribute(): int
    {
        return $this->visitors()->count();
    }

    public function getTotalConversionsAttribute(): int
    {
        return $this->convertedVisitors()->count();
    }

    public function getConversionRateAttribute(): float
    {
        $total = $this->total_visitors;
        return $total > 0 ? round(($this->total_conversions / $total) * 100, 2) : 0;
    }

    public function getBudgetRemainingAttribute(): float
    {
        return max(0, $this->budget - $this->spent);
    }

    public function getBudgetUtilizationAttribute(): float
    {
        return $this->budget > 0 ? round(($this->spent / $this->budget) * 100, 2) : 0;
    }

    public function getCostPerLeadAttribute(): float
    {
        $total = $this->total_visitors;
        return $total > 0 ? round($this->spent / $total, 2) : 0;
    }

    public function getCostPerConversionAttribute(): float
    {
        $conversions = $this->total_conversions;
        return $conversions > 0 ? round($this->spent / $conversions, 2) : 0;
    }

    public function getDaysRunningAttribute(): int
    {
        if (!$this->start_date) return 0;
        $end = $this->end_date && $this->end_date->isPast() ? $this->end_date : now();
        return $this->start_date->diffInDays($end);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' 
            && $this->start_date && $this->start_date->isPast()
            && (!$this->end_date || $this->end_date->isFuture());
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed' 
            || ($this->end_date && $this->end_date->isPast());
    }

    // ── Helpers ──

    public function activate(): void
    {
        $this->update([
            'status' => 'active',
            'start_date' => $this->start_date ?? now(),
        ]);
    }

    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'end_date' => $this->end_date ?? now(),
        ]);
    }

    public function addExpense(float $amount, string $description = null): void
    {
        $this->increment('spent', $amount);
    }

    public function trackConversion(): void
    {
        // Conversion is tracked via visitor relationship
        // This method can be used for additional tracking logic
    }

    public function meetsTarget(): bool
    {
        if ($this->target_conversions && $this->total_conversions >= $this->target_conversions) {
            return true;
        }
        if ($this->target_visitors && $this->total_visitors >= $this->target_visitors) {
            return true;
        }
        return false;
    }
}

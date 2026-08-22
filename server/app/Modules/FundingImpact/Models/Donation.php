<?php

namespace App\Modules\FundingImpact\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Donation Model
 *
 * Represents a donation received from a donor.
 * Includes relationships to donor, campaign, and financial transactions.
 */
class Donation extends Model
{
    use HasUuids;

    protected $fillable = [
        'donor_id',
        'campaign_id',
        'amount',
        'currency',
        'donation_date',
        'payment_method',
        'receipt_number',
        'is_recurring',
        'recurrence_frequency',
        'notes',
        'branch_id',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'donation_date' => 'date',
        'is_recurring' => 'boolean',
    ];

    protected $attributes = [
        'currency' => 'USD',
        'status' => 'completed',
        'is_recurring' => false,
    ];

    // ── Relationships ──

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Iam\Models\Branch::class);
    }

    public function scholarships(): HasMany
    {
        return $this->hasMany(Scholarship::class);
    }

    public function financialTransactions(): MorphMany
    {
        return $this->morphMany(\App\Modules\FinancePayroll\Models\FinancialTransaction::class, 'referenceable');
    }

    // ── Scopes ──

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    public function scopeOneTime($query)
    {
        return $query->where('is_recurring', false);
    }

    public function scopeByBranch($query, string $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByDonor($query, string $donorId)
    {
        return $query->where('donor_id', $donorId);
    }

    public function scopeByCampaign($query, string $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }

    public function scopeBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('donation_date', [$startDate, $endDate]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('donation_date', now()->month)
                     ->whereYear('donation_date', now()->year);
    }

    public function scopeThisYear($query)
    {
        return $query->whereYear('donation_date', now()->year);
    }

    // ── Accessors ──

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    public function getDaysAgoAttribute(): int
    {
        return $this->donation_date->diffInDays(now());
    }

    // ── Helpers ──

    public static function generateReceiptNumber(): string
    {
        $year = now()->format('Y');
        $lastDonation = static::whereYear('donation_date', $year)
            ->orderByDesc('created_at')
            ->first();

        $sequence = $lastDonation
            ? (int)substr($lastDonation->receipt_number, -6) + 1
            : 1;

        return sprintf('DON-%s-%06d', $year, $sequence);
    }
}

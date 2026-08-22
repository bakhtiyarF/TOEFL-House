<?php

namespace App\Modules\FinancePayroll\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Invoice Model
 *
 * Represents an invoice issued to a student.
 * Includes relationships to student, payments, and invoice items.
 */
class Invoice extends Model
{
    use HasUuids;

    protected $fillable = [
        'invoice_number',
        'student_id',
        'enrollment_id',
        'branch_id',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'status',
        'due_date',
        'paid_at',
        'notes',
        'issued_by',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'draft',
        'discount_amount' => 0,
        'tax_amount' => 0,
    ];

    // ── Relationships ──

    public function student(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Academic\Models\Student::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Academic\Models\Enrollment::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Iam\Models\Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Iam\Models\User::class, 'issued_by');
    }

    // ── Scopes ──

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeIssued($query)
    {
        return $query->where('status', 'issued');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
                     ->orWhere(function ($q) {
                         $q->where('status', 'issued')
                           ->where('due_date', '<', now());
                     });
    }

    public function scopeByBranch($query, string $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByStudent($query, string $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeDueBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('due_date', [$startDate, $endDate]);
    }

    // ── Accessors ──

    public function getAmountPaidAttribute(): float
    {
        return $this->payments()->where('status', 'completed')->sum('amount');
    }

    public function getAmountDueAttribute(): float
    {
        return max(0, $this->total_amount - $this->amount_paid);
    }

    public function isPaid(): bool
    {
        return $this->amount_paid >= $this->total_amount;
    }

    public function isOverdue(): bool
    {
        return !$this->isPaid() && $this->due_date && $this->due_date->isPast();
    }

    public function getDaysOverdueAttribute(): int
    {
        if (!$this->isOverdue()) return 0;
        return $this->due_date->diffInDays(now());
    }

    // ── Helpers ──

    public function calculateTotals(): void
    {
        $subtotal = $this->items->sum('total');
        $discount = $this->discount_amount ?? 0;
        $tax = $this->tax_amount ?? 0;
        $total = max(0, $subtotal - $discount + $tax);

        $this->update([
            'subtotal' => $subtotal,
            'total_amount' => $total,
        ]);
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    public function markAsOverdue(): void
    {
        $this->update([
            'status' => 'overdue',
        ]);
    }

    public static function generateInvoiceNumber(): string
    {
        $year = now()->format('Y');
        $lastInvoice = static::whereYear('created_at', $year)
            ->orderByDesc('created_at')
            ->first();

        $sequence = $lastInvoice
            ? (int)substr($lastInvoice->invoice_number, -6) + 1
            : 1;

        return sprintf('INV-%s-%06d', $year, $sequence);
    }
}

<?php

namespace App\Modules\FinancePayroll\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Payment Model
 *
 * Represents a payment made by a student.
 * Includes relationships to student, invoice, and financial transaction.
 */
class Payment extends Model
{
    use HasUuids;

    protected $fillable = [
        'student_id',
        'invoice_id',
        'amount',
        'date',
        'payment_method',
        'status',
        'category',
        'notes',
        'receipt_number',
        'semester',
        'branch_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
    ];

    protected $attributes = [
        'status' => 'completed',
        'category' => 'tuition',
    ];

    // ── Relationships ──

    public function student(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Academic\Models\Student::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Iam\Models\Branch::class);
    }

    public function financialTransaction(): HasOne
    {
        return $this->hasOne(FinancialTransaction::class, 'reference_id')
            ->where('type', 'income')
            ->where('category', 'payment');
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

    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }

    public function scopeByBranch($query, string $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByStudent($query, string $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByMethod($query, string $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('date', now()->month)
                     ->whereYear('date', now()->year);
    }

    public function scopeThisYear($query)
    {
        return $query->whereYear('date', now()->year);
    }

    // ── Accessors ──

    public function getPaymentMethodLabelAttribute(): string
    {
        return match($this->payment_method) {
            'cash' => 'Cash',
            'card' => 'Card',
            'bank_transfer' => 'Bank Transfer',
            default => ucfirst($this->payment_method),
        };
    }

    public function getCategoryLabelAttribute(): string
    {
        return match($this->category) {
            'tuition' => 'Tuition Fee',
            'registration' => 'Registration Fee',
            'exam' => 'Exam Fee',
            'book' => 'Book Purchase',
            'card' => 'ID Card Fee',
            'placement' => 'Placement Test Fee',
            'diploma' => 'Diploma Fee',
            'other' => 'Other',
            default => ucfirst($this->category),
        };
    }

    // ── Helpers ──

    public function isRefundable(): bool
    {
        return $this->status === 'completed' && $this->date->gte(now()->subDays(30));
    }

    public static function generateReceiptNumber(): string
    {
        $year = now()->format('Y');
        $lastPayment = static::whereYear('date', $year)
            ->orderByDesc('created_at')
            ->first();

        $sequence = $lastPayment
            ? (int)substr($lastPayment->receipt_number, -6) + 1
            : 1;

        return sprintf('RCP-%s-%06d', $year, $sequence);
    }
}

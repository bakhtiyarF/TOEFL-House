<?php

use App\Modules\Academic\Models\Enrollment;
use App\Modules\Academic\Models\Student;
use App\Modules\FinancePayroll\Models\Invoice;
use App\Modules\FinancePayroll\Models\Payment;
use App\Modules\FundingImpact\Models\Campaign;
use App\Modules\FundingImpact\Models\Donation;
use App\Modules\FundingImpact\Models\Donor;
use App\Modules\Iam\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Finance Operations', function () {
    
    it('can create an invoice with multiple items', function () {
        $branch = Branch::factory()->create();
        $student = Student::factory()->create(['branch_id' => $branch->id]);

        $invoice = Invoice::create([
            'student_id' => $student->id,
            'branch_id' => $branch->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'issued',
        ]);

        $invoice->items()->createMany([
            ['description' => 'Tuition Fee', 'quantity' => 1, 'unit_price' => 15000],
            ['description' => 'Registration Fee', 'quantity' => 1, 'unit_price' => 2000],
            ['description' => 'Books', 'quantity' => 3, 'unit_price' => 500],
        ]);

        $subtotal = $invoice->items->sum(fn($item) => $item->quantity * $item->unit_price);
        
        expect($invoice->items()->count())->toBe(3);
        expect($subtotal)->toBe(18500);
    });

    it('can record a payment against an invoice', function () {
        $branch = Branch::factory()->create();
        $student = Student::factory()->create(['branch_id' => $branch->id]);
        $invoice = Invoice::factory()->create([
            'student_id' => $student->id,
            'branch_id' => $branch->id,
            'total_amount' => 15000,
            'status' => 'issued',
        ]);

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'student_id' => $student->id,
            'branch_id' => $branch->id,
            'amount' => 15000,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        expect($payment)->toBeInstanceOf(Payment::class);
        expect($payment->amount)->toBe(15000);
        expect($payment->status)->toBe('completed');
    });

    it('can track partial payments', function () {
        $branch = Branch::factory()->create();
        $student = Student::factory()->create(['branch_id' => $branch->id]);
        $invoice = Invoice::factory()->create([
            'student_id' => $student->id,
            'branch_id' => $branch->id,
            'total_amount' => 15000,
            'status' => 'issued',
        ]);

        // First partial payment
        Payment::create([
            'invoice_id' => $invoice->id,
            'student_id' => $student->id,
            'branch_id' => $branch->id,
            'amount' => 5000,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        // Second partial payment
        Payment::create([
            'invoice_id' => $invoice->id,
            'student_id' => $student->id,
            'branch_id' => $branch->id,
            'amount' => 7000,
            'payment_date' => now()->addDays(5)->toDateString(),
            'payment_method' => 'bank_transfer',
            'status' => 'completed',
        ]);

        $totalPaid = $invoice->payments()->where('status', 'completed')->sum('amount');
        $remaining = $invoice->total_amount - $totalPaid;

        expect($totalPaid)->toBe(12000);
        expect($remaining)->toBe(3000);
    });

    it('can handle refunds', function () {
        $branch = Branch::factory()->create();
        $student = Student::factory()->create(['branch_id' => $branch->id]);
        $invoice = Invoice::factory()->create([
            'student_id' => $student->id,
            'branch_id' => $branch->id,
            'total_amount' => 10000,
            'status' => 'paid',
        ]);

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'student_id' => $student->id,
            'branch_id' => $branch->id,
            'amount' => 10000,
            'payment_date' => now()->subDays(5)->toDateString(),
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        // Process refund
        $refund = Payment::create([
            'invoice_id' => $invoice->id,
            'student_id' => $student->id,
            'branch_id' => $branch->id,
            'amount' => -5000, // Negative amount for refund
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'status' => 'refunded',
            'notes' => 'Partial refund processed',
        ]);

        $netAmount = $invoice->payments()->sum('amount');

        expect($refund->amount)->toBe(-5000);
        expect($refund->status)->toBe('refunded');
        expect($netAmount)->toBe(5000);
    });

    it('can create a donation', function () {
        $branch = Branch::factory()->create();
        $donor = Donor::factory()->create();

        $donation = Donation::create([
            'donor_id' => $donor->id,
            'branch_id' => $branch->id,
            'amount' => 50000,
            'donation_date' => now()->toDateString(),
            'payment_method' => 'bank_transfer',
            'designation' => 'scholarship',
            'send_receipt' => true,
        ]);

        expect($donation)->toBeInstanceOf(Donation::class);
        expect($donation->amount)->toBe(50000);
        expect($donation->designation)->toBe('scholarship');
    });

    it('can track campaign donations', function () {
        $branch = Branch::factory()->create();
        $campaign = Campaign::factory()->create([
            'branch_id' => $branch->id,
            'goal_amount' => 100000,
            'status' => 'active',
        ]);

        $donors = Donor::factory()->count(3)->create();

        foreach ($donors as $index => $donor) {
            Donation::create([
                'donor_id' => $donor->id,
                'campaign_id' => $campaign->id,
                'branch_id' => $branch->id,
                'amount' => 20000 + ($index * 5000),
                'donation_date' => now()->toDateString(),
                'payment_method' => 'check',
            ]);
        }

        $totalDonations = $campaign->donations()->sum('amount');
        $progressPercentage = ($totalDonations / $campaign->goal_amount) * 100;

        expect($campaign->donations()->count())->toBe(3);
        expect($totalDonations)->toBe(75000);
        expect($progressPercentage)->toBe(75.0);
    });

    it('can calculate monthly revenue', function () {
        $branch = Branch::factory()->create();
        $student = Student::factory()->create(['branch_id' => $branch->id]);

        // Create payments for current month
        for ($i = 0; $i < 5; $i++) {
            Payment::create([
                'student_id' => $student->id,
                'branch_id' => $branch->id,
                'amount' => 10000,
                'payment_date' => now()->subDays($i)->toDateString(),
                'payment_method' => 'cash',
                'status' => 'completed',
            ]);
        }

        // Create payments for previous month
        for ($i = 0; $i < 3; $i++) {
            Payment::create([
                'student_id' => $student->id,
                'branch_id' => $branch->id,
                'amount' => 10000,
                'payment_date' => now()->subMonth()->subDays($i)->toDateString(),
                'payment_method' => 'cash',
                'status' => 'completed',
            ]);
        }

        $currentMonthRevenue = Payment::where('branch_id', $branch->id)
            ->where('status', 'completed')
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount');

        $previousMonthRevenue = Payment::where('branch_id', $branch->id)
            ->where('status', 'completed')
            ->whereMonth('payment_date', now()->subMonth()->month)
            ->whereYear('payment_date', now()->subMonth()->year)
            ->sum('amount');

        expect($currentMonthRevenue)->toBe(50000);
        expect($previousMonthRevenue)->toBe(30000);
    });

    it('can track outstanding balances', function () {
        $branch = Branch::factory()->create();
        $students = Student::factory()->count(5)->create(['branch_id' => $branch->id]);

        foreach ($students as $index => $student) {
            $invoice = Invoice::factory()->create([
                'student_id' => $student->id,
                'branch_id' => $branch->id,
                'total_amount' => 15000,
                'status' => 'issued',
            ]);

            // Pay varying amounts
            $paidAmount = ($index + 1) * 3000; // 3000, 6000, 9000, 12000, 15000
            
            if ($paidAmount > 0) {
                Payment::create([
                    'invoice_id' => $invoice->id,
                    'student_id' => $student->id,
                    'branch_id' => $branch->id,
                    'amount' => $paidAmount,
                    'payment_date' => now()->toDateString(),
                    'payment_method' => 'cash',
                    'status' => 'completed',
                ]);
            }
        }

        $totalOutstanding = Invoice::where('branch_id', $branch->id)
            ->where('status', 'issued')
            ->get()
            ->sum(function ($invoice) {
                $paid = $invoice->payments()->where('status', 'completed')->sum('amount');
                return max(0, $invoice->total_amount - $paid);
            });

        // Expected: (15000-3000) + (15000-6000) + (15000-9000) + (15000-12000) + (15000-15000)
        // = 12000 + 9000 + 6000 + 3000 + 0 = 30000
        expect($totalOutstanding)->toBe(30000);
    });
});

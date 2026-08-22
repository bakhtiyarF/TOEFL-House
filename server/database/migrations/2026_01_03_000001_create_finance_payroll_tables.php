<?php

/**
 * Finance & Payroll Module — Database Tables
 * Per 07_FINANCE_AND_PAYROLL_MODULE.md §4
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Budget Lines
        Schema::create('budget_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->decimal('current_amount', 14, 2)->default(0);
            $table->decimal('allocated_amount', 14, 2)->default(0);
            $table->string('icon', 50)->nullable();
            $table->enum('cost_type', ['fixed', 'variable'])->default('fixed');
            $table->boolean('is_marketing')->default(false);
            $table->string('purpose'); // semantic lookup key (02 §8.5)
            $table->uuid('branch_id');
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches');
        });

        // Financial Transactions — the master ledger
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('type', ['income', 'expense', 'saving_transfer', 'budget_charge']);
            $table->string('category');
            $table->decimal('amount', 14, 2);
            $table->date('date');
            $table->text('description')->nullable();
            $table->uuid('reference_id')->nullable(); // polymorphic: payment/expense/book_sale
            $table->string('operator_name')->nullable();
            $table->uuid('branch_id');
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches');
            $table->index(['branch_id', 'date']);
            $table->index(['type', 'category']);
        });

        // Payments
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id')->nullable();
            $table->uuid('invoice_id')->nullable();
            $table->decimal('amount', 14, 2);
            $table->date('date');
            $table->enum('payment_method', ['cash', 'card', 'bank_transfer']);
            $table->enum('status', ['completed', 'pending', 'failed', 'refunded'])->default('completed');
            $table->enum('category', ['fee', 'book', 'chapter', 'exam', 'card', 'placement', 'diploma', 'other']);
            $table->text('notes')->nullable();
            $table->string('receipt_number')->nullable();
            $table->string('semester')->nullable();
            $table->uuid('branch_id');
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students');
            $table->foreign('branch_id')->references('id')->on('branches');
        });

        // Invoices
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->decimal('total_amount', 14, 2);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('net_amount', 14, 2);
            $table->enum('status', ['draft', 'issued', 'paid', 'partial', 'overdue', 'cancelled'])->default('draft');
            $table->date('issue_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('invoice_number')->nullable();
            $table->uuid('issued_by')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('branch_id');
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students');
            $table->foreign('branch_id')->references('id')->on('branches');
        });

        // Invoice Items
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('invoice_id');
            $table->string('description');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 14, 2);
            $table->decimal('amount', 14, 2);
            $table->timestamps();

            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
        });

        // Expense Requests
        Schema::create('expense_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->decimal('amount', 14, 2);
            $table->uuid('budget_line_id')->nullable();
            $table->string('requester')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->date('date');
            $table->uuid('approved_by')->nullable();
            $table->text('reject_reason')->nullable();
            $table->enum('expense_kind', ['recurring_bill', 'one_time_purchase', 'maintenance', 'other'])->default('other');
            $table->string('bill_period')->nullable();
            $table->enum('payment_method', ['cash', 'card', 'bank_transfer'])->nullable();
            $table->text('notes')->nullable();
            $table->boolean('auto_approved')->default(false);
            $table->uuid('workflow_instance_id')->nullable();
            $table->uuid('branch_id');
            $table->timestamps();

            $table->foreign('budget_line_id')->references('id')->on('budget_lines');
            $table->foreign('branch_id')->references('id')->on('branches');
        });

        // Teacher Salary Ledger
        Schema::create('teacher_salary_ledger', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('teacher_id'); // FK added when People & HR builds teachers
            $table->string('period_key'); // YYYY-MM normalized
            $table->string('period_label');
            $table->decimal('due_amount', 14, 2);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->enum('payment_type', ['full', 'partial', 'advance']);
            $table->uuid('transaction_id')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('branch_id');
            $table->timestamp('paid_at')->nullable();
            $table->string('operator_name')->nullable();
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches');
            $table->index(['teacher_id', 'period_key']);
        });

        // Teacher Level Skill Rates
        Schema::create('teacher_level_skill_rates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('teacher_id'); // FK added when People & HR builds teachers
            $table->uuid('level_id')->nullable();
            $table->string('level_code');
            $table->uuid('skill_id')->nullable(); // null = level-wide rate
            $table->decimal('rate_per_skill', 14, 2);
            $table->uuid('branch_id');
            $table->timestamps();

            $table->unique(['teacher_id', 'level_code', 'skill_id']);
            $table->foreign('branch_id')->references('id')->on('branches');
        });

        // Partners (moved from People & HR, 06 §2)
        Schema::create('partners', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('full_name');
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->decimal('share_percent', 5, 2)->default(0);
            $table->text('role_description')->nullable();
            $table->timestamps();
        });

        // System Settings (key-value store, 07 §4 / 08 §4)
        Schema::create('system_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Seed default financial settings
        DB::table('system_settings')->insert([
            ['key' => 'daily_saving_percent', 'value' => '5', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'saving_balance', 'value' => '0', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'main_account_balance', 'value' => '0', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('partners');
        Schema::dropIfExists('teacher_level_skill_rates');
        Schema::dropIfExists('teacher_salary_ledger');
        Schema::dropIfExists('expense_requests');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('financial_transactions');
        Schema::dropIfExists('budget_lines');
    }
};

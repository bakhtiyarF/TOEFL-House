<?php

/**
 * CRM & Enrollment, Inventory, and Funding & Impact tables
 * Per docs 09, 10, 11
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --- CRM & Enrollment (doc 09) ---

        Schema::create('campaigns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->enum('source', ['ads', 'social', 'referral', 'event', 'organic', 'other']);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('budget', 14, 2)->default(0);
            $table->enum('status', ['active', 'paused', 'completed'])->default('active');
            $table->uuid('branch_id');
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches');
        });

        Schema::create('visitors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('serial_no')->nullable();
            $table->string('full_name');
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->string('source')->nullable();
            $table->uuid('campaign_id')->nullable();
            $table->enum('stage', [
                'lead', 'inquiry', 'follow_up', 'placement_booking',
                'placement_completed', 'registration', 'enrollment',
                'active', 'graduated', 'alumni', 'lost'
            ])->default('lead');
            $table->uuid('assigned_to')->nullable();
            $table->date('visit_date')->nullable();
            $table->string('status')->default('visited');
            $table->text('notes')->nullable();
            $table->uuid('branch_id');
            $table->string('interested_course')->nullable();
            $table->string('follow_up_status')->nullable();
            $table->date('next_contact_date')->nullable();
            $table->string('father_name')->nullable();
            $table->string('address_region')->nullable();
            $table->string('tazkira_no', 50)->nullable();
            $table->string('whatsapp', 50)->nullable();
            $table->date('dob')->nullable();
            $table->string('school_or_university')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 50)->nullable();
            $table->json('placement_score')->nullable();
            $table->timestamps();

            $table->foreign('campaign_id')->references('id')->on('campaigns');
            $table->foreign('branch_id')->references('id')->on('branches');
        });

        Schema::create('visitor_followups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('visitor_id');
            $table->date('date');
            $table->text('notes')->nullable();
            $table->string('operator')->nullable();
            $table->enum('outcome', ['interested', 'not_interested', 'callback', 'registered']);
            $table->timestamps();

            $table->foreign('visitor_id')->references('id')->on('visitors')->cascadeOnDelete();
        });

        // --- Inventory (doc 10) ---

        Schema::create('books', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->decimal('price', 12, 2);
            $table->decimal('purchase_price', 12, 2)->nullable();
            $table->integer('stock')->default(0);
            $table->boolean('is_chapter')->default(false);
            $table->uuid('branch_id');
            $table->date('entry_date')->nullable();
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches');
        });

        Schema::create('book_restock_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('book_id');
            $table->date('date');
            $table->integer('quantity');
            $table->decimal('price', 12, 2);
            $table->decimal('purchase_price', 12, 2)->nullable();
            $table->timestamps();

            $table->foreign('book_id')->references('id')->on('books')->cascadeOnDelete();
        });

        Schema::create('book_sales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('book_id');
            $table->integer('quantity');
            $table->decimal('total_amount', 14, 2);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('net_amount', 14, 2);
            $table->enum('payment_method', ['cash', 'card', 'bank_transfer']); // normalized to bank_transfer (10 §4)
            $table->enum('status', ['completed', 'refunded'])->default('completed');
            $table->date('date');
            $table->string('customer_name')->nullable();
            $table->uuid('student_id')->nullable();
            $table->uuid('branch_id');
            $table->timestamps();

            $table->foreign('book_id')->references('id')->on('books');
            $table->foreign('student_id')->references('id')->on('students');
            $table->foreign('branch_id')->references('id')->on('branches');
        });

        // --- Funding & Impact (doc 11) ---

        Schema::create('donors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('full_name');
            $table->enum('type', ['individual', 'organization', 'ngo', 'government']);
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('country')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('funding_campaigns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->uuid('donor_id')->nullable();
            $table->decimal('target_amount', 14, 2)->default(0);
            $table->decimal('raised_amount', 14, 2)->default(0);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->uuid('branch_id');
            $table->timestamps();

            $table->foreign('donor_id')->references('id')->on('donors');
            $table->foreign('branch_id')->references('id')->on('branches');
        });

        Schema::create('donations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('campaign_id')->nullable();
            $table->uuid('donor_id');
            $table->decimal('amount', 14, 2);
            $table->date('date');
            $table->boolean('restricted')->default(false);
            $table->text('restriction_note')->nullable();
            $table->string('receipt_no')->nullable();
            $table->uuid('branch_id');
            $table->timestamps();

            $table->foreign('campaign_id')->references('id')->on('funding_campaigns');
            $table->foreign('donor_id')->references('id')->on('donors');
            $table->foreign('branch_id')->references('id')->on('branches');
        });

        Schema::create('scholarships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->uuid('donor_id')->nullable();
            $table->uuid('campaign_id')->nullable();
            $table->decimal('total_budget', 14, 2);
            $table->decimal('allocated_amount', 14, 2)->default(0);
            $table->text('criteria')->nullable();
            $table->enum('status', ['active', 'exhausted', 'closed'])->default('active');
            $table->uuid('branch_id');
            $table->timestamps();

            $table->foreign('donor_id')->references('id')->on('donors');
            $table->foreign('campaign_id')->references('id')->on('funding_campaigns');
            $table->foreign('branch_id')->references('id')->on('branches');
        });

        Schema::create('scholarship_awards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('scholarship_id');
            $table->uuid('student_id');
            $table->decimal('amount', 14, 2);
            $table->date('award_date');
            $table->string('semester')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('branch_id');
            $table->timestamps();

            $table->foreign('scholarship_id')->references('id')->on('scholarships')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches');
        });

        Schema::create('sponsorship_agreements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('donor_id');
            $table->uuid('student_id')->nullable();
            $table->uuid('program_id')->nullable();
            $table->decimal('monthly_amount', 14, 2);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['active', 'completed', 'terminated'])->default('active');
            $table->uuid('branch_id');
            $table->timestamps();

            $table->foreign('donor_id')->references('id')->on('donors');
            $table->foreign('student_id')->references('id')->on('students');
            $table->foreign('program_id')->references('id')->on('programs');
            $table->foreign('branch_id')->references('id')->on('branches');
        });

        Schema::create('impact_metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->enum('category', ['academic', 'social', 'economic', 'demographic']);
            $table->decimal('target_value', 14, 2)->default(0);
            $table->decimal('current_value', 14, 2)->default(0);
            $table->string('period')->nullable();
            $table->uuid('branch_id');
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches');
        });

        Schema::create('impact_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->uuid('donor_id')->nullable();
            $table->uuid('campaign_id')->nullable();
            $table->string('period')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->json('metrics')->nullable();
            $table->text('narrative')->nullable();
            $table->enum('status', ['draft', 'published', 'sent'])->default('draft');
            $table->uuid('branch_id');
            $table->timestamps();

            $table->foreign('donor_id')->references('id')->on('donors');
            $table->foreign('campaign_id')->references('id')->on('funding_campaigns');
            $table->foreign('branch_id')->references('id')->on('branches');
        });

        Schema::create('success_stories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->string('title');
            $table->text('content');
            $table->string('photo_url')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->json('tags')->default('[]');
            $table->uuid('branch_id');
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('success_stories');
        Schema::dropIfExists('impact_reports');
        Schema::dropIfExists('impact_metrics');
        Schema::dropIfExists('sponsorship_agreements');
        Schema::dropIfExists('scholarship_awards');
        Schema::dropIfExists('scholarships');
        Schema::dropIfExists('donations');
        Schema::dropIfExists('funding_campaigns');
        Schema::dropIfExists('donors');
        Schema::dropIfExists('book_sales');
        Schema::dropIfExists('book_restock_history');
        Schema::dropIfExists('books');
        Schema::dropIfExists('visitor_followups');
        Schema::dropIfExists('visitors');
        Schema::dropIfExists('campaigns');
    }
};

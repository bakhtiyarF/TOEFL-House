<?php

/**
 * Academic Module — Curriculum Catalog Tables
 * Per 05_ACADEMIC_MODULE.md §4.1
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Programs — org-level catalog identity
        Schema::create('programs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('duration_months')->nullable();
            $table->string('code', 50)->unique();
            $table->boolean('is_active')->default(true);
            $table->uuid('branch_id');
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches');
        });

        // Program Versions — the non-negotiable copy-on-write anchor (02 §4)
        Schema::create('program_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('program_id');
            $table->string('version_label');
            $table->integer('version_number');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['program_id', 'version_number']);
            $table->foreign('program_id')->references('id')->on('programs')->cascadeOnDelete();
        });

        // Levels
        Schema::create('levels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('program_id');
            $table->uuid('program_version_id');
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->integer('order')->default(0);
            $table->integer('duration_months')->nullable();
            $table->decimal('default_fee', 12, 2)->default(0);
            $table->integer('pass_mark')->default(60);
            $table->integer('min_viable_size')->default(5);
            $table->timestamps();

            $table->foreign('program_id')->references('id')->on('programs')->cascadeOnDelete();
            $table->foreign('program_version_id')->references('id')->on('program_versions')->cascadeOnDelete();
        });

        // Subjects (versioned)
        Schema::create('subjects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('program_version_id');
            $table->uuid('level_id');
            $table->string('code', 50);
            $table->string('name');
            $table->integer('hours')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['program_version_id', 'code']);
            $table->foreign('program_version_id')->references('id')->on('program_versions')->cascadeOnDelete();
            $table->foreign('level_id')->references('id')->on('levels')->cascadeOnDelete();
        });

        // Modules (within subjects)
        Schema::create('modules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('subject_id');
            $table->string('code', 50);
            $table->string('name');
            $table->integer('hours')->nullable();
            $table->integer('sort_order')->default(0);
            $table->string('assessment_type', 50)->nullable();
            $table->timestamps();

            $table->unique(['subject_id', 'code']);
            $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
        });

        // Skills lookup (Reading, Writing, Listening, Speaking)
        Schema::create('skills', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // Placement Rules — score band → recommended level
        Schema::create('placement_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('program_version_id');
            $table->string('name');
            $table->integer('min_score');
            $table->integer('max_score');
            $table->uuid('recommended_level_id');
            $table->uuid('branch_id')->nullable(); // branch override
            $table->integer('sort_order')->default(0);
            $table->integer('version')->default(1);
            $table->timestamps();

            $table->foreign('program_version_id')->references('id')->on('program_versions');
            $table->foreign('recommended_level_id')->references('id')->on('levels');
            $table->foreign('branch_id')->references('id')->on('branches');
        });

        // Promotion Rules — score + attendance based (05 §5 — authoritative mechanism)
        Schema::create('promotion_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('program_version_id');
            $table->uuid('from_level_id');
            $table->uuid('to_level_id');
            $table->string('name');
            $table->integer('min_score')->default(60);
            $table->integer('min_attendance_pct')->default(75);
            $table->boolean('require_all_subjects')->default(false);
            $table->boolean('auto_promote')->default(false);
            $table->uuid('branch_id')->nullable(); // branch override
            $table->integer('version')->default(1);
            $table->timestamps();

            $table->foreign('program_version_id')->references('id')->on('program_versions');
            $table->foreign('from_level_id')->references('id')->on('levels');
            $table->foreign('to_level_id')->references('id')->on('levels');
            $table->foreign('branch_id')->references('id')->on('branches');
        });

        // Fee Rules — per program version / level / branch
        Schema::create('fee_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('program_version_id')->nullable();
            $table->uuid('level_id')->nullable();
            $table->uuid('branch_id')->nullable();
            $table->enum('fee_type', [
                'registration', 'placement', 'semester', 'book',
                'retake', 'diploma', 'card', 'exam', 'other'
            ]);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('AFN');
            $table->boolean('is_optional')->default(false);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->integer('version')->default(1);
            $table->timestamps();

            $table->foreign('program_version_id')->references('id')->on('program_versions');
            $table->foreign('level_id')->references('id')->on('levels');
            $table->foreign('branch_id')->references('id')->on('branches');
        });

        // Branch Academic Profiles — per-branch independence knobs
        Schema::create('branch_academic_profiles', function (Blueprint $table) {
            $table->uuid('branch_id')->primary();
            $table->uuid('default_program_version_id')->nullable();
            $table->decimal('placement_test_fee', 12, 2)->default(300);
            $table->decimal('registration_fee', 12, 2)->default(0);
            $table->decimal('card_fee', 12, 2)->default(200);
            $table->decimal('diploma_fee', 12, 2)->default(500);
            $table->integer('default_pass_mark')->default(60);
            $table->integer('default_min_attendance')->default(75);
            $table->string('academic_year_label', 50)->nullable();
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            $table->foreign('default_program_version_id')->references('id')->on('program_versions');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_academic_profiles');
        Schema::dropIfExists('fee_rules');
        Schema::dropIfExists('promotion_rules');
        Schema::dropIfExists('placement_rules');
        Schema::dropIfExists('skills');
        Schema::dropIfExists('modules');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('levels');
        Schema::dropIfExists('program_versions');
        Schema::dropIfExists('programs');
    }
};

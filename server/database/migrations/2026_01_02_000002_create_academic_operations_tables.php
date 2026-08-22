<?php

/**
 * Academic Module — Students, Enrollment & Assessment Tables
 * Per 05_ACADEMIC_MODULE.md §4.2–4.3
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Students
        Schema::create('students', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('student_code')->unique();
            $table->string('full_name');
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('qr_code')->nullable();
            $table->enum('status', ['active', 'inactive', 'graduated', 'suspended'])->default('active');
            $table->date('registration_date');
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->uuid('lead_id')->nullable(); // FK to visitors (added when CRM builds)
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->string('father_name')->nullable();
            $table->string('address_region')->nullable();
            $table->string('tazkira_no', 50)->nullable();
            $table->string('whatsapp', 50)->nullable();
            $table->date('dob')->nullable();
            $table->string('school_or_university')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 50)->nullable();
            $table->json('placement_score')->nullable();
            $table->string('installment_plan')->nullable();
            $table->string('card_design')->nullable();
            $table->uuid('branch_id');
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches');
        });

        // Classes
        Schema::create('classes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->uuid('teacher_id')->nullable(); // FK added when People & HR builds teachers
            $table->uuid('program_id')->nullable();
            $table->uuid('level_id')->nullable();
            $table->string('level')->nullable(); // derived display text
            $table->integer('capacity')->default(20);
            $table->integer('min_viable_size')->default(5);
            $table->string('schedule_time')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->decimal('fee', 12, 2)->default(0);
            $table->enum('gender_policy', ['female', 'male', 'mixed'])->default('mixed');
            $table->uuid('room_id')->nullable();
            $table->uuid('time_slot_id')->nullable();
            $table->uuid('academic_term_id')->nullable();
            $table->date('activation_date')->nullable();
            $table->uuid('merged_into_id')->nullable(); // self-reference for merge workflow
            $table->text('notes')->nullable();
            $table->uuid('branch_id');
            $table->timestamps();

            $table->foreign('program_id')->references('id')->on('programs');
            $table->foreign('level_id')->references('id')->on('levels');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('merged_into_id')->references('id')->on('classes')->nullOnDelete();
        });

        // Class-Teacher-Skills linkage
        Schema::create('class_teacher_skills', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('class_id');
            $table->uuid('teacher_id'); // FK added when People & HR builds teachers
            $table->uuid('skill_id'); // FK added when People & HR builds skills
            $table->decimal('monthly_rate', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['class_id', 'teacher_id', 'skill_id']);
            $table->foreign('class_id')->references('id')->on('classes')->cascadeOnDelete();
        });

        // Sessions
        Schema::create('sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('class_id');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('topic')->nullable();
            $table->enum('status', ['scheduled', 'completed', 'cancelled'])->default('scheduled');
            $table->uuid('teacher_id')->nullable();
            $table->timestamps();

            $table->foreign('class_id')->references('id')->on('classes')->cascadeOnDelete();
        });

        // Rosters — current attendance mechanism (05 §5)
        Schema::create('rosters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('session_id');
            $table->uuid('student_id');
            $table->enum('attendance_status', ['present', 'absent', 'sick', 'leave', 'not_marked'])->default('not_marked');
            $table->timestamp('marked_at')->nullable();
            $table->timestamps();

            $table->unique(['session_id', 'student_id']);
            $table->foreign('session_id')->references('id')->on('sessions')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
        });

        // Homework
        Schema::create('homework', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('session_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->uuid('assigned_by');
            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('sessions')->cascadeOnDelete();
        });

        // Student Semesters — enrollment count source for payroll
        Schema::create('student_semesters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->string('semester_name');
            $table->uuid('class_id');
            $table->date('enroll_date');
            $table->decimal('fee_amount', 12, 2)->default(0);
            $table->enum('status', ['active', 'completed', 'deferred'])->default('active');
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('class_id')->references('id')->on('classes');
        });

        // Enrollments — the copy-on-write pin (05 §6)
        Schema::create('enrollments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('program_id');
            $table->string('program_name');
            $table->string('semester_name')->nullable();
            $table->string('level_code')->nullable();
            $table->uuid('class_id')->nullable();
            $table->uuid('program_version_id'); // copy-on-write pin
            $table->json('fee_snapshot_json'); // copy-on-write pin
            $table->enum('enrollment_type', ['new', 'repeat', 'partial_repeat', 'resume', 'jump'])->default('new');
            $table->enum('status', ['active', 'paused', 'suspended', 'dropped', 'completed', 'graduated'])->default('active');
            $table->text('skills_focus')->nullable();
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('scholarship_percent', 5, 2)->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->uuid('branch_id');
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('program_id')->references('id')->on('programs');
            $table->foreign('program_version_id')->references('id')->on('program_versions');
            $table->foreign('class_id')->references('id')->on('classes');
            $table->foreign('branch_id')->references('id')->on('branches');
        });

        // Exams
        Schema::create('exams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->date('date');
            $table->decimal('fee', 12, 2)->default(0);
            $table->uuid('class_id')->nullable();
            $table->enum('type', ['placement', 'midterm', 'final', 'mock', 'certification']);
            $table->uuid('branch_id');
            $table->timestamps();

            $table->foreign('class_id')->references('id')->on('classes');
            $table->foreign('branch_id')->references('id')->on('branches');
        });

        // Exam Results
        Schema::create('exam_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('exam_id');
            $table->uuid('student_id');
            $table->decimal('score', 5, 2);
            $table->string('status', 20)->nullable();
            $table->boolean('exam_fee_paid')->default(false);
            $table->boolean('certificate_issued')->default(false);
            $table->string('certificate_no')->nullable();
            $table->timestamps();

            $table->foreign('exam_id')->references('id')->on('exams')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
        });

        // Certificates
        Schema::create('certificates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('program_id')->nullable();
            $table->uuid('level_id')->nullable();
            $table->date('issue_date');
            $table->string('certificate_no')->unique();
            $table->string('grade')->nullable();
            $table->uuid('branch_id');
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('program_id')->references('id')->on('programs');
            $table->foreign('level_id')->references('id')->on('levels');
            $table->foreign('branch_id')->references('id')->on('branches');
        });

        // Student Journey Events — append-only event log (05 §4.3)
        Schema::create('student_journey_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->string('event_type'); // STUDENT_REGISTERED, ENROLLMENT_CREATED, etc.
            $table->timestamp('occurred_at');
            $table->uuid('enrollment_id')->nullable();
            $table->json('payload')->nullable();
            $table->uuid('actor_user_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->uuid('correlation_id')->nullable();
            $table->uuid('causation_id')->nullable();
            $table->integer('schema_version')->default(1);
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('enrollment_id')->references('id')->on('enrollments');
            $table->index(['student_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_journey_events');
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('exam_results');
        Schema::dropIfExists('exams');
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('student_semesters');
        Schema::dropIfExists('homework');
        Schema::dropIfExists('rosters');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('class_teacher_skills');
        Schema::dropIfExists('classes');
        Schema::dropIfExists('students');
    }
};

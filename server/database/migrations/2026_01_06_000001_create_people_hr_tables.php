<?php

/**
 * People & HR Module — Teachers and Employees
 * Per 06_PEOPLE_HR_MODULE.md §4
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Teachers
        Schema::create('teachers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('full_name');
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->decimal('base_salary', 14, 2)->default(0);
            $table->enum('salary_type', ['fixed', 'per_skill', 'per_session', 'hybrid', 'per_level']);
            $table->decimal('performance_score', 5, 2)->default(0);
            $table->enum('status', ['active', 'inactive', 'on_leave'])->default('active');
            $table->uuid('branch_id');
            $table->date('joined_date');
            $table->string('specialization')->nullable();
            $table->string('qualification')->nullable();
            $table->enum('contract_type', ['monthly', 'hourly', 'per_session'])->nullable();
            $table->uuid('user_id')->nullable(); // one stored direction (05 §5 discrepancy #4)
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        // Employees (non-teaching staff)
        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('full_name');
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('role')->nullable(); // job title (not IAM role)
            $table->decimal('base_salary', 14, 2)->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->uuid('branch_id');
            $table->date('joined_date');
            $table->uuid('user_id')->nullable();
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        // Teacher Evaluations (moved here from Academic per 06 §2)
        Schema::create('teacher_evaluations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('teacher_id');
            $table->uuid('evaluator_id'); // intentionally NOT FK-constrained (06 §4)
            $table->date('date');
            $table->decimal('score', 5, 2)->default(0);
            $table->json('criteria')->default('{}');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('teacher_id')->references('id')->on('teachers')->cascadeOnDelete();
        });

        // Now add deferred FK constraints from Academic module (05 §5, 06 §5)
        Schema::table('classes', function (Blueprint $table) {
            $table->foreign('teacher_id')->references('id')->on('teachers')->nullOnDelete();
        });

        Schema::table('class_teacher_skills', function (Blueprint $table) {
            $table->foreign('teacher_id')->references('id')->on('teachers')->cascadeOnDelete();
            $table->foreign('skill_id')->references('id')->on('skills')->cascadeOnDelete();
        });

        Schema::table('teacher_salary_ledger', function (Blueprint $table) {
            $table->foreign('teacher_id')->references('id')->on('teachers')->cascadeOnDelete();
        });

        Schema::table('teacher_level_skill_rates', function (Blueprint $table) {
            $table->foreign('teacher_id')->references('id')->on('teachers')->cascadeOnDelete();
            $table->foreign('skill_id')->references('id')->on('skills')->nullOnDelete();
        });

        // Seed default TOEFL skills (06 §5)
        DB::table('skills')->insert([
            ['id' => Str::uuid()->toString(), 'name' => 'Reading', 'created_at' => now(), 'updated_at' => now()],
            ['id' => Str::uuid()->toString(), 'name' => 'Writing', 'created_at' => now(), 'updated_at' => now()],
            ['id' => Str::uuid()->toString(), 'name' => 'Listening', 'created_at' => now(), 'updated_at' => now()],
            ['id' => Str::uuid()->toString(), 'name' => 'Speaking', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::table('teacher_level_skill_rates', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
            $table->dropForeign(['skill_id']);
        });
        Schema::table('teacher_salary_ledger', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
        });
        Schema::table('class_teacher_skills', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
            $table->dropForeign(['skill_id']);
        });
        Schema::table('classes', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
        });

        Schema::dropIfExists('teacher_evaluations');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('teachers');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('username', 50)->unique();
            $table->string('password');
            $table->string('full_name');
            $table->string('employee_id', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('address')->nullable();
            $table->string('national_id', 50)->nullable();
            $table->string('emergency_contact')->nullable();
            $table->string('department', 100)->nullable();
            $table->string('employment_type', 50)->nullable();
            $table->string('employee_status', 50)->nullable();
            $table->string('profile_photo_path')->nullable();
            $table->string('account_status', 50)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->date('joining_date')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->uuid('manager_user_id')->nullable();
            $table->enum('role', [
                'owner', 'manager', 'finance', 'registrar',
                'teacher', 'head_of_department', 'counselor', 'donor_manager'
            ]);
            $table->uuid('branch_id');
            $table->uuid('linked_teacher_id')->nullable();
            $table->uuid('linked_employee_id')->nullable();
            $table->uuid('linked_partner_id')->nullable();
            $table->boolean('two_factor_enabled')->default(false);
            $table->boolean('must_change_password')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('manager_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

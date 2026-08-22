<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(100);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique(); // Resource.Action format
            $table->string('resource');
            $table->string('action');
            $table->text('description')->nullable();
            $table->string('category')->default('general');
            $table->boolean('is_system')->default(true);
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('role_id');
            $table->uuid('permission_id');
            $table->enum('default_scope', [
                'organization', 'campus', 'branch',
                'department', 'program', 'class', 'own'
            ])->default('branch');
            $table->timestamps();

            $table->unique(['role_id', 'permission_id']);
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('role_id');
            $table->enum('scope_type', [
                'organization', 'campus', 'branch',
                'department', 'program', 'class', 'own'
            ])->default('branch');
            $table->uuid('scope_id')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->uuid('assigned_by')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'role_id', 'scope_type', 'scope_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
        });

        Schema::create('permission_overrides', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('permission_id');
            $table->enum('effect', ['grant', 'deny']);
            $table->enum('scope_type', [
                'organization', 'campus', 'branch',
                'department', 'program', 'class', 'own'
            ])->default('branch');
            $table->uuid('scope_id')->nullable();
            $table->text('reason')->nullable();
            $table->uuid('granted_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
        });

        Schema::create('role_delegations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('from_user_id');
            $table->uuid('to_user_id');
            $table->uuid('role_id');
            $table->enum('scope_type', [
                'organization', 'campus', 'branch',
                'department', 'program', 'class', 'own'
            ])->default('branch');
            $table->uuid('scope_id')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at'); // always required
            $table->uuid('created_by')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('from_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('to_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_delegations');
        Schema::dropIfExists('permission_overrides');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};

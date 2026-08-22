<?php

/**
 * Platform Services Module — Rule Engine, Events, Workflows, Notifications, Audit
 * Per 08_PLATFORM_SERVICES_MODULE.md §4
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rule Definitions — the configurable rule engine
        Schema::create('rule_definitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('category', [
                'fee', 'discount', 'promotion', 'attendance',
                'payroll', 'scholarship', 'workflow', 'notification',
                'finance', 'academic'
            ]);
            $table->json('conditions');
            $table->json('actions');
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->uuid('scope_branch_id')->nullable(); // null = org-wide
            $table->integer('version')->default(1);
            $table->uuid('last_modified_by')->nullable();
            $table->timestamp('last_modified_at')->nullable();
            $table->timestamps();

            $table->foreign('scope_branch_id')->references('id')->on('branches');
            $table->index(['category', 'is_active']);
        });

        // Rule Versions — full history on every edit
        Schema::create('rule_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('rule_id');
            $table->integer('version');
            $table->json('conditions');
            $table->json('actions');
            $table->integer('priority');
            $table->boolean('is_active');
            $table->uuid('modified_by')->nullable();
            $table->timestamp('modified_at');
            $table->timestamps();

            $table->unique(['rule_id', 'version']);
            $table->foreign('rule_id')->references('id')->on('rule_definitions')->cascadeOnDelete();
        });

        // Rule Evaluation Logs
        Schema::create('rule_evaluation_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('rule_id');
            $table->string('category');
            $table->uuid('branch_id')->nullable();
            $table->boolean('matched');
            $table->json('context_json')->nullable();
            $table->json('result_json')->nullable();
            $table->boolean('dry_run')->default(false);
            $table->timestamp('evaluated_at');

            $table->foreign('rule_id')->references('id')->on('rule_definitions')->cascadeOnDelete();
        });

        // Workflow Definitions
        Schema::create('workflow_definitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('trigger'); // event type e.g. 'expense.requested'
            $table->json('steps');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Workflow Instances
        Schema::create('workflow_instances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('definition_id');
            $table->string('entity_type');
            $table->uuid('entity_id');
            $table->integer('current_step')->default(0);
            $table->enum('status', ['pending', 'in_progress', 'approved', 'rejected', 'completed', 'cancelled'])->default('pending');
            $table->uuid('branch_id');
            $table->uuid('initiated_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->foreign('definition_id')->references('id')->on('workflow_definitions');
            $table->foreign('branch_id')->references('id')->on('branches');
        });

        // Workflow History — append-only
        Schema::create('workflow_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('instance_id');
            $table->integer('step_order');
            $table->string('actor');
            $table->string('action');
            $table->text('notes')->nullable();
            $table->timestamp('timestamp');

            $table->foreign('instance_id')->references('id')->on('workflow_instances')->cascadeOnDelete();
        });

        // Domain Events — append-only event store
        Schema::create('domain_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->string('aggregate_type');
            $table->uuid('aggregate_id');
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at');
            $table->uuid('operator_id')->nullable();
            $table->uuid('branch_id');
            $table->uuid('correlation_id')->nullable();
            $table->uuid('causation_id')->nullable();
            $table->integer('schema_version')->default(1);
            $table->boolean('published')->default(false);
            $table->json('metadata')->nullable();

            $table->foreign('branch_id')->references('id')->on('branches');
            $table->index(['type', 'aggregate_type', 'aggregate_id']);
        });

        // Event Handler Log — at-most-once execution
        Schema::create('event_handler_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('event_id');
            $table->string('handler');
            $table->boolean('success');
            $table->integer('duration_ms')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('executed_at');

            $table->unique(['event_id', 'handler']);
            $table->foreign('event_id')->references('id')->on('domain_events')->cascadeOnDelete();
        });

        // Event Subscriptions — fan-out table
        Schema::create('event_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('event_type');
            $table->enum('handler', ['workflow', 'automation', 'notification', 'webhook']);
            $table->json('config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->nullable();
        });

        // Notifications
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('message');
            $table->date('date');
            $table->boolean('read')->default(false);
            $table->enum('type', ['info', 'warning', 'critical', 'success'])->default('info');
            $table->uuid('user_id')->nullable();
            $table->string('link')->nullable();
            $table->uuid('branch_id')->nullable();
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches');
            $table->index(['user_id', 'read']);
        });

        // Audit Logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('operator_id')->nullable();
            $table->string('operator_name')->nullable();
            $table->string('action');
            $table->date('date');
            $table->time('time');
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('device')->nullable();
            $table->uuid('branch_id');
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches');
            $table->index(['branch_id', 'date']);
        });

        // Automations
        Schema::create('automations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('trigger');
            $table->json('conditions')->nullable();
            $table->json('actions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Pipeline Metrics
        Schema::create('pipeline_metrics', function (Blueprint $table) {
            $table->string('pipeline');
            $table->string('stage');
            $table->integer('count')->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);
            $table->decimal('average_time_in_stage', 8, 2)->default(0);
            $table->uuid('branch_id');
            $table->timestamp('computed_at');

            $table->primary(['pipeline', 'stage', 'branch_id']);
            $table->foreign('branch_id')->references('id')->on('branches');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pipeline_metrics');
        Schema::dropIfExists('automations');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('event_subscriptions');
        Schema::dropIfExists('event_handler_log');
        Schema::dropIfExists('domain_events');
        Schema::dropIfExists('workflow_history');
        Schema::dropIfExists('workflow_instances');
        Schema::dropIfExists('workflow_definitions');
        Schema::dropIfExists('rule_evaluation_logs');
        Schema::dropIfExists('rule_versions');
        Schema::dropIfExists('rule_definitions');
    }
};

<?php

/**
 * Add performance indexes to frequently queried columns
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Students — most queried table
        Schema::table('students', function (Blueprint $table) {
            $table->index(['branch_id', 'status'], 'idx_students_branch_status');
            $table->index('full_name', 'idx_students_name');
            $table->index('created_at', 'idx_students_created');
        });

        // Classes
        Schema::table('classes', function (Blueprint $table) {
            $table->index(['branch_id', 'status'], 'idx_classes_branch_status');
            $table->index('teacher_id', 'idx_classes_teacher');
        });

        // Sessions
        Schema::table('sessions', function (Blueprint $table) {
            $table->index(['class_id', 'date'], 'idx_sessions_class_date');
        });

        // Rosters (attendance)
        Schema::table('rosters', function (Blueprint $table) {
            $table->index(['session_id', 'attendance_status'], 'idx_rosters_session_status');
        });

        // Student semesters
        Schema::table('student_semesters', function (Blueprint $table) {
            $table->index(['class_id', 'status'], 'idx_semesters_class_status');
            $table->index(['student_id', 'status'], 'idx_semesters_student_status');
        });

        // Enrollments
        Schema::table('enrollments', function (Blueprint $table) {
            $table->index(['student_id', 'status'], 'idx_enrollments_student_status');
            $table->index('program_version_id', 'idx_enrollments_version');
        });

        // Financial transactions
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->index(['branch_id', 'type', 'date'], 'idx_transactions_branch_type_date');
        });

        // Payments
        Schema::table('payments', function (Blueprint $table) {
            $table->index(['branch_id', 'status', 'date'], 'idx_payments_branch_status_date');
            $table->index('student_id', 'idx_payments_student');
        });

        // Visitors
        Schema::table('visitors', function (Blueprint $table) {
            $table->index(['branch_id', 'stage'], 'idx_visitors_branch_stage');
            $table->index('created_at', 'idx_visitors_created');
        });

        // Notifications
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id', 'read', 'created_at'], 'idx_notifications_user_read');
        });

        // Audit logs
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['branch_id', 'date'], 'idx_audit_branch_date');
        });

        // Teachers
        Schema::table('teachers', function (Blueprint $table) {
            $table->index(['branch_id', 'status'], 'idx_teachers_branch_status');
        });

        // Books
        Schema::table('books', function (Blueprint $table) {
            $table->index(['branch_id', 'stock'], 'idx_books_branch_stock');
        });
    }

    public function down(): void
    {
        Schema::table('students', fn(Blueprint $t) => $t->dropIndex(['branch_id', 'status']));
        Schema::table('classes', fn(Blueprint $t) => $t->dropIndex(['branch_id', 'status']));
        Schema::table('sessions', fn(Blueprint $t) => $t->dropIndex(['class_id', 'date']));
        Schema::table('rosters', fn(Blueprint $t) => $t->dropIndex(['session_id', 'attendance_status']));
        Schema::table('student_semesters', fn(Blueprint $t) => $t->dropIndex(['class_id', 'status']));
        Schema::table('enrollments', fn(Blueprint $t) => $t->dropIndex(['student_id', 'status']));
        Schema::table('financial_transactions', fn(Blueprint $t) => $t->dropIndex(['branch_id', 'type', 'date']));
        Schema::table('payments', fn(Blueprint $t) => $t->dropIndex(['branch_id', 'status', 'date']));
        Schema::table('visitors', fn(Blueprint $t) => $t->dropIndex(['branch_id', 'stage']));
        Schema::table('notifications', fn(Blueprint $t) => $t->dropIndex(['user_id', 'read', 'created_at']));
        Schema::table('audit_logs', fn(Blueprint $t) => $t->dropIndex(['branch_id', 'date']));
        Schema::table('teachers', fn(Blueprint $t) => $t->dropIndex(['branch_id', 'status']));
        Schema::table('books', fn(Blueprint $t) => $t->dropIndex(['branch_id', 'stock']));
    }
};

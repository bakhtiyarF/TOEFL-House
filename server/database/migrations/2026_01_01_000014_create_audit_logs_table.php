<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
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
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};

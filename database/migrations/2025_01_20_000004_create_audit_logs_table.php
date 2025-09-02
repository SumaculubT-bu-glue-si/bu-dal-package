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
            $table->id();
            $table->foreignId('audit_plan_id')->constrained('audit_plans')->onDelete('cascade');
            $table->foreignId('asset_id')->nullable()->constrained('assets')->onDelete('cascade');
            $table->string('action'); // Created, Updated, Completed, Status Changed, etc.
            $table->json('old_values')->nullable(); // Previous state
            $table->json('new_values')->nullable(); // New state
            $table->string('performed_by'); // User/employee who performed the action
            $table->text('description')->nullable(); // Human-readable description of the action
            $table->string('ip_address')->nullable(); // For security tracking
            $table->string('user_agent')->nullable(); // For security tracking
            $table->timestamps();
            
            // Indexes for better query performance
            $table->index(['audit_plan_id', 'action']);
            $table->index('asset_id');
            $table->index('performed_by');
            $table->index('created_at');
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

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
        Schema::create('corrective_action_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('corrective_action_id')->constrained('corrective_actions')->onDelete('cascade');
            $table->foreignId('audit_assignment_id')->constrained('audit_assignments')->onDelete('cascade');
            $table->foreignId('assigned_to_employee_id')->nullable()->constrained('employees')->onDelete('set null');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'overdue'])->default('pending');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('progress_notes')->nullable();
            $table->timestamps();
            
            // Indexes for better query performance
            $table->index(['corrective_action_id', 'audit_assignment_id'], 'caa_ca_aa_idx');
            $table->index('assigned_to_employee_id', 'caa_employee_idx');
            $table->index('status', 'caa_status_idx');
            $table->index('assigned_at', 'caa_assigned_idx');
            
            // Ensure unique assignment per corrective action
            $table->unique('corrective_action_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('corrective_action_assignments');
    }
};

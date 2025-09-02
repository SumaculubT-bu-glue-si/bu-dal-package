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
        Schema::create('audit_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_plan_id')->constrained('audit_plans')->onDelete('cascade');
            $table->foreignId('location_id')->constrained('locations')->onDelete('cascade');
            $table->foreignId('auditor_id')->constrained('employees')->onDelete('cascade');
            $table->timestamp('assigned_at')->useCurrent();
            $table->enum('status', ['Assigned', 'In Progress', 'Completed'])->default('Assigned');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Indexes for better query performance
            $table->index(['audit_plan_id', 'location_id']);
            $table->index('auditor_id');
            $table->index('status');
            
            // Ensure unique assignment per plan-location combination
            $table->unique(['audit_plan_id', 'location_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_assignments');
    }
};

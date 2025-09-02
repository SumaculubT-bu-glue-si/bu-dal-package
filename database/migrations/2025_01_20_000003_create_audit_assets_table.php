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
        Schema::create('audit_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_plan_id')->constrained('audit_plans')->onDelete('cascade');
            $table->foreignId('asset_id')->constrained('assets')->onDelete('cascade');
            $table->string('original_location'); // Snapshot of asset location when plan created
            $table->string('original_user')->nullable(); // Snapshot of asset user when plan created
            $table->enum('current_status', [
                'Pending', 
                'Found', 
                'In Storage', 
                'Needs Repair', 
                'Missing', 
                'Scheduled for Disposal'
            ])->default('Pending');
            $table->text('auditor_notes')->nullable(); // Auditor findings and notes
            $table->timestamp('audited_at')->nullable(); // When the asset was audited
            $table->boolean('resolved')->default(false); // Whether issues were resolved
            $table->string('audited_by')->nullable(); // Who audited this asset
            $table->string('current_location')->nullable(); // Current location if different from original
            $table->string('current_user')->nullable(); // Current user if different from original
            $table->timestamps();
            
            // Indexes for better query performance
            $table->index(['audit_plan_id', 'current_status']);
            $table->index('asset_id');
            $table->index('current_status');
            $table->index('resolved');
            
            // Ensure unique asset per audit plan
            $table->unique(['audit_plan_id', 'asset_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_assets');
    }
};

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
        Schema::table('audit_assignments', function (Blueprint $table) {
            // Drop the existing unique constraint that prevents multiple auditors per location
            $table->dropUnique(['audit_plan_id', 'location_id']);
            
            // Add a new unique constraint that prevents duplicate assignments
            // (same auditor assigned to same location in same plan multiple times)
            $table->unique(['audit_plan_id', 'location_id', 'auditor_id'], 'unique_audit_assignment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_assignments', function (Blueprint $table) {
            // Drop the new constraint
            $table->dropUnique('unique_audit_assignment');
            
            // Restore the old constraint
            $table->unique(['audit_plan_id', 'location_id']);
        });
    }
};

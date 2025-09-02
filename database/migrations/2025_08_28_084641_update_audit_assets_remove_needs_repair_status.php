<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update any existing 'Needs Repair' status values to 'Broken'
        DB::table('audit_assets')
            ->where('current_status', 'Needs Repair')
            ->update(['current_status' => 'Broken']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert 'Broken' status values back to 'Needs Repair' if needed
        DB::table('audit_assets')
            ->where('current_status', 'Broken')
            ->update(['current_status' => 'Needs Repair']);
    }
};

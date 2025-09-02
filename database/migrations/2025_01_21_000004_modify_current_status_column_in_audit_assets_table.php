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
        Schema::table('audit_assets', function (Blueprint $table) {
            // Drop the enum constraint and change to string
            $table->string('current_status')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_assets', function (Blueprint $table) {
            // Revert back to enum if needed
            $table->enum('current_status', [
                'Pending',
                'Found',
                'In Storage',
                'Needs Repair',
                'Missing',
                'Scheduled for Disposal'
            ])->change();
        });
    }
};

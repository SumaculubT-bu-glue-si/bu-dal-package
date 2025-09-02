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
        // First, convert empty strings to NULL for serial_number
        DB::table('assets')->where('serial_number', '')->update(['serial_number' => null]);
        
        Schema::table('assets', function (Blueprint $table) {
            // Drop the unique constraint first
            $table->dropUnique(['serial_number']);
        });
        
        Schema::table('assets', function (Blueprint $table) {
            // Drop the column and recreate it as nullable
            $table->dropColumn('serial_number');
        });
        
        Schema::table('assets', function (Blueprint $table) {
            // Recreate the column as nullable
            $table->string('serial_number')->nullable()->after('part_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            // Drop the nullable column
            $table->dropColumn('serial_number');
        });
        
        Schema::table('assets', function (Blueprint $table) {
            // Recreate the original non-nullable column
            $table->string('serial_number')->after('part_number');
        });
        
        Schema::table('assets', function (Blueprint $table) {
            // Re-add the unique constraint
            $table->unique(['serial_number']);
        });
    }
};

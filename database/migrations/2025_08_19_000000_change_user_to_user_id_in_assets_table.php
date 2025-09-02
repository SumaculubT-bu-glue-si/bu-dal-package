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
        Schema::table('assets', function (Blueprint $table) {
            // First, add the new user_id column
            $table->unsignedBigInteger('user_id')->nullable()->after('user');
            
            // Add foreign key constraint
            $table->foreign('user_id')->references('id')->on('employees')->onDelete('set null');
        });

        // Copy data from user column to user_id column
        // This will be handled in the seeder or manually
        
        // Remove the old user column
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn('user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            // Add back the user column
            $table->string('user')->nullable()->after('user_id');
            
            // Remove foreign key constraint
            $table->dropForeign(['user_id']);
            
            // Remove the user_id column
            $table->dropColumn('user_id');
        });
    }
};

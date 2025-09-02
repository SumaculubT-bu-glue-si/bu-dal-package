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
            // Make required fields nullable to accommodate imported data
            $table->string('manufacturer')->nullable()->change();
            $table->string('model')->nullable()->change();
            $table->string('location')->nullable()->change();
            $table->string('status')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            // Revert fields back to required
            $table->string('manufacturer')->nullable(false)->change();
            $table->string('model')->nullable(false)->change();
            $table->string('location')->nullable(false)->change();
            $table->string('status')->nullable(false)->change();
        });
    }
};

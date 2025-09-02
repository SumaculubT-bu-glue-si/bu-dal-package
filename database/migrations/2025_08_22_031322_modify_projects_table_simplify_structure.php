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
        Schema::table('projects', function (Blueprint $table) {
            // Drop columns that are not needed
            $table->dropColumn([
                'status',
                'start_date',
                'end_date',
                'manager',
                'client',
                'budget',
                'priority'
            ]);
            
            // Add the visible column
            $table->boolean('visible')->default(true)->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Re-add the dropped columns
            $table->string('status')->default('active');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('manager')->nullable();
            $table->string('client')->nullable();
            $table->decimal('budget', 10, 2)->nullable();
            $table->string('priority')->default('medium');
            
            // Drop the visible column
            $table->dropColumn('visible');
        });
    }
};

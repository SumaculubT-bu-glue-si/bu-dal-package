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
            $table->boolean('audit_status')->default(false)->after('resolved');
            $table->index('audit_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_assets', function (Blueprint $table) {
            $table->dropIndex(['audit_status']);
            $table->dropColumn('audit_status');
        });
    }
};

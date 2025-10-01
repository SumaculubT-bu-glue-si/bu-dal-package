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
        Schema::table('audit_plans', function (Blueprint $table) {
            $table->string('chat_space_id')->nullable()->after('id');
            $table->string('chat_space_name')->nullable()->after('chat_space_id');
            $table->timestamp('chat_space_created_at')->nullable()->after('chat_space_name');
            $table->boolean('chat_space_cleanup_scheduled')->default(false)->after('chat_space_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_plans', function (Blueprint $table) {
            $table->dropColumn([
                'chat_space_id',
                'chat_space_name',
                'chat_space_created_at',
                'chat_space_cleanup_scheduled'
            ]);
        });
    }
};
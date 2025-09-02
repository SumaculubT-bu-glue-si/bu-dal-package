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
        // Update asset statuses from Japanese to English
        DB::table('assets')->where('status', '返却済')->update(['status' => 'Returned']);
        DB::table('assets')->where('status', '廃止')->update(['status' => 'Abolished']);
        DB::table('assets')->where('status', '保管(使用無)')->update(['status' => 'Stored - Not in Use']);
        DB::table('assets')->where('status', '利用中')->update(['status' => 'In Use']);
        DB::table('assets')->where('status', '保管中')->update(['status' => 'In Storage']);
        DB::table('assets')->where('status', '貸出中')->update(['status' => 'On Loan']);
        DB::table('assets')->where('status', '故障中')->update(['status' => 'Broken']);
        DB::table('assets')->where('status', '利用予約')->update(['status' => 'Reserved for Use']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert asset statuses from English to Japanese
        DB::table('assets')->where('status', 'Returned')->update(['status' => '返却済']);
        DB::table('assets')->where('status', 'Abolished')->update(['status' => '廃止']);
        DB::table('assets')->where('status', 'Stored - Not in Use')->update(['status' => '保管(使用無)']);
        DB::table('assets')->where('status', 'In Use')->update(['status' => '利用中']);
        DB::table('assets')->where('status', 'In Storage')->update(['status' => '保管中']);
        DB::table('assets')->where('status', 'On Loan')->update(['status' => '貸出中']);
        DB::table('assets')->where('status', 'Broken')->update(['status' => '故障中']);
        DB::table('assets')->where('status', 'Reserved for Use')->update(['status' => '利用予約']);
    }
};

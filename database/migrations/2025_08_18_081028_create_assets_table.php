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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_id')->unique(); // PC001, PC002, etc.
            $table->string('type'); // pc, monitor, phone
            $table->string('hostname')->nullable();
            $table->string('manufacturer');
            $table->string('model');
            $table->string('part_number')->nullable();
            $table->string('serial_number')->unique();
            $table->string('form_factor')->nullable(); // Laptop, Desktop, etc.
            $table->string('os')->nullable();
            $table->string('os_bit')->nullable();
            $table->string('office_suite')->nullable();
            $table->string('software_license_key')->nullable();
            $table->string('wired_mac_address')->nullable();
            $table->string('wired_ip_address')->nullable();
            $table->string('wireless_mac_address')->nullable();
            $table->string('wireless_ip_address')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->integer('depreciation_years')->nullable();
            $table->string('depreciation_dept')->nullable();
            $table->string('cpu')->nullable();
            $table->string('memory')->nullable();
            $table->string('location');
            $table->string('status'); // 返却済, 廃止, 保管(使用無), 利用中, 保管中, 貸出中, 故障中, 利用予約
            $table->string('previous_user')->nullable();
            $table->string('user')->nullable(); // Current assigned user
            $table->date('usage_start_date')->nullable();
            $table->date('usage_end_date')->nullable();
            $table->string('carry_in_out_agreement')->nullable();
            $table->timestamp('last_updated')->nullable();
            $table->string('updated_by')->nullable();
            $table->text('notes')->nullable();
            $table->string('project')->nullable();
            $table->text('notes1')->nullable();
            $table->text('notes2')->nullable();
            $table->text('notes3')->nullable();
            $table->text('notes4')->nullable();
            $table->text('notes5')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};

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
        Schema::dropIfExists('loans');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->string('loan_id')->unique(); // loan-1, loan-2, etc.
            $table->string('asset_id'); // PC001, SP002, etc.
            $table->string('employee_id'); // EMP001, EMP002, etc.
            $table->date('loan_date');
            $table->date('expected_return_date')->nullable();
            $table->date('actual_return_date')->nullable();
            $table->string('status'); // On Loan, Returned, Overdue
            $table->timestamps();
        });
    }
};

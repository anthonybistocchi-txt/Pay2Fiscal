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
        Schema::create('transaction_fiscal_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')
                ->constrained('transactions')
                ->cascadeOnDelete()
                ->unique();
            $table->unsignedSmallInteger('origin_id')->nullable();
            $table->string('ncm', 8)->nullable();
            $table->string('cfop', 4)->nullable();
            $table->string('cest', 7)->nullable();
            $table->string('icms_cst_csosn', 4)->nullable();
            $table->string('pis_cst', 2)->nullable();
            $table->string('cofins_cst', 2)->nullable();
            $table->unsignedSmallInteger('fiscal_response_code')->nullable();
            $table->string('fiscal_request_id')->unique()->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_fiscal_data');
    }
};

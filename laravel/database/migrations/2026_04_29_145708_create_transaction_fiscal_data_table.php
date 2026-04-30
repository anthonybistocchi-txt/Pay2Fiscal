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
            $table->enum('fiscal_status', ['PENDING', 'PROCESSING', 'EMITTED', 'REJECTED', 'ERROR', 'CANCELED'])
                ->default('PENDING');
            $table->unsignedSmallInteger('origin_product')->nullable();
            $table->string('ncm', 8)->nullable();
            $table->string('cfop', 4)->nullable();
            $table->string('cest', 7)->nullable();
            $table->string('icms_cst_csosn', 4)->nullable();
            $table->string('pis_cst', 2)->nullable();
            $table->string('cofins_cst', 2)->nullable();
            $table->string('fiscal_request_id')->unique()->nullable();
            $table->text('failure_reason')->nullable();
            $table->unsignedSmallInteger('error_code')->nullable();
            $table->dateTime('emitted_at')->nullable();
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

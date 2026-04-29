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

            // Snapshot fiscal do item (1 item por transação por enquanto).
            $table->unsignedSmallInteger('origin_id')->nullable(); // Origem da mercadoria (0..8)
            $table->string('ncm', 8)->nullable(); // NCM (8 dígitos, pode ter 0 à esquerda)
            $table->string('cfop', 4)->nullable(); // CFOP (4 dígitos)
            $table->string('cest', 7)->nullable(); // CEST (7 dígitos, pode ter 0 à esquerda)

            $table->string('icms_cst_csosn', 4)->nullable(); // CST/CSOSN
            $table->string('pis_cst', 2)->nullable(); // PIS CST
            $table->string('cofins_cst', 2)->nullable(); // COFINS CST

            // Resultado/retorno do processamento fiscal (emissão/tributação).
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

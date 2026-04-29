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
        Schema::create('product_fiscal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->unique();

            // Cadastro fiscal do produto (fonte dos códigos).
            $table->unsignedSmallInteger('origin_id')->nullable(); // Origem da mercadoria (0..8)
            $table->string('ncm', 8)->nullable(); // NCM (8 dígitos, pode ter 0 à esquerda)
            $table->string('cest', 7)->nullable(); // CEST (7 dígitos, pode ter 0 à esquerda)
            $table->string('cfop', 4)->nullable(); // CFOP default (pode variar por operação/UF)

            $table->string('icms_cst_csosn', 4)->nullable(); // CST/CSOSN
            $table->string('pis_cst', 2)->nullable(); // PIS CST
            $table->string('cofins_cst', 2)->nullable(); // COFINS CST

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_fiscal');
    }
};

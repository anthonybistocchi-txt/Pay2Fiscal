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
        Schema::create('emitters', function (Blueprint $table) {
            $table->id();
            $table->string('legal_name');          // Razão social
            $table->string('trade_name')->nullable(); // Nome fantasia
            $table->string('cnpj', 14)->unique();  // Apenas dígitos
            $table->string('ie')->nullable();      // Inscrição estadual
            $table->string('im')->nullable();      // Inscrição municipal (quando aplicável)

            // Regime/CRT (por enquanto string, pode virar enum depois)
            $table->string('tax_regime')->nullable(); // ex: SIMPLES, NORMAL
            $table->string('crt', 1)->nullable();     // ex: 1,2,3 (CRT)

            // Endereço do emitente
            $table->string('street');
            $table->string('number');
            $table->string('complement')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('city');
            $table->string('state', 2);
            $table->string('zip_code', 8);
            $table->string('country', 2)->default('BR');

            // Contato
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emitters');
    }
};

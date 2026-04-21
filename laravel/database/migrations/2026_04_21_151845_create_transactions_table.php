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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('product_id')->constrained('products');
            $table->integer('amount');
            $table->enum('payment_method', ['CREDIT_CARD', 'DEBIT_CARD', 'PIX', 'BOLETO']);
            $table->enum('payment_status', ['PENDING', 'APPROVED', 'REJECTED', 'ERROR', 'REFUNDED']);
            $table->dateTime('payment_date');
            $table->integer('payment_amount');
            $table->string('idempotency_key')->unique(); 
            $table->uuid('transaction_id')->unique();
            $table->string('last_4_digits_card_number');
            $table->string('card_brand');
            $table->integer('gateway_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

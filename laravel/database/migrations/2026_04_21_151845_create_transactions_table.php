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
            $table->integer('payment_amount');
            $table->enum('payment_method', ['CREDIT_CARD', 'DEBIT_CARD', 'PIX', 'BOLETO']);
            $table->enum('payment_status', ['PENDING', 'APPROVED', 'REJECTED', 'ERROR', 'REFUNDED']);
            $table->dateTime('payment_date')->nullable();
            $table->uuid('idempotency_key')->unique();
            $table->uuid('transaction_uuid')->unique();
            $table->string('last_4_digits_card_number')->nullable();
            $table->string('card_brand')->nullable();
            $table->integer('gateway_id')->nullable();
            $table->integer('quantity');
            $table->dateTime('dispatched_at')->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->dateTime('failed_at')->nullable();
            $table->string('failure_reason')->nullable();
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

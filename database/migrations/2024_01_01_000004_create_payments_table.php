<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->restrictOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            
            // Stripe fields
            $table->string('stripe_payment_intent_id', 100)->nullable();
            $table->string('stripe_payment_method_id', 100)->nullable();
            $table->string('stripe_customer_id', 100)->nullable();
            
            // Amount
            $table->integer('amount_cents');
            $table->string('currency', 3)->default('usd');
            
            // Type and status
            $table->string('payment_type', 30); // deposit, full, late_fee, no_show_charge, squeeze_in_fee
            $table->string('status', 30)->default('pending'); // pending, succeeded, failed, refunded
            
            // Metadata
            $table->json('stripe_metadata')->nullable();
            $table->text('failure_reason')->nullable();
            
            $table->timestamps();

            $table->unique('stripe_payment_intent_id');
            $table->index(['appointment_id', 'payment_type']);
            $table->index(['client_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

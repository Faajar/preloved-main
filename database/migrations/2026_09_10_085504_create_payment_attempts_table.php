<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('sequence')->default(1);
            $table->string('provider');
            $table->string('status')->default('initialized');
            $table->string('provider_request_id')->nullable();
            $table->string('invoice_number')->nullable();
            $table->string('provider_reference')->nullable();
            $table->text('checkout_url')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('succeeded_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->json('request_payload')->nullable();
            $table->string('request_payload_hash', 64)->nullable();
            $table->timestamp('request_timestamp')->nullable();
            $table->unsignedInteger('request_count')->default(0);
            $table->text('last_provider_error')->nullable();
            $table->timestamps();

            $table->unique(['payment_id', 'sequence']);
            $table->unique(['provider', 'provider_request_id']);
            $table->unique('invoice_number');
            $table->index(['payment_id', 'status', 'sequence']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_attempts');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_attempt_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider');
            $table->string('provider_refund_reference')->nullable();
            $table->string('provider_status')->nullable();
            $table->string('kind');
            $table->unsignedBigInteger('amount');
            $table->string('currency', 3);
            $table->text('reason')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamp('received_at')->useCurrent();
            $table->boolean('requires_review')->default(false);
            $table->timestamps();

            $table->unique(['provider', 'provider_refund_reference']);
            $table->index(['payment_id', 'provider_status', 'occurred_at']);
            $table->index(['requires_review', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_refunds');
    }
};

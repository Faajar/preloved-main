<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_fulfillments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->unique()->constrained()->restrictOnDelete();
            $table->string('status')->default('pending');
            $table->unsignedInteger('boost_duration_hours')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->text('last_failure')->nullable();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'next_retry_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_fulfillments');
    }
};

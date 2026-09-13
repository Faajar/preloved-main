<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_webhook_events', function (Blueprint $table) {
            $table->foreignId('payment_attempt_id')->nullable()->after('payment_id')->constrained()->nullOnDelete();
            $table->string('provider_status')->nullable()->after('payload_hash');
            $table->string('processing_status')->default('received')->after('provider_status');
            $table->json('payload')->nullable()->after('processing_status');
            $table->unsignedInteger('processing_attempts')->default(0)->after('payload');
            $table->timestamp('next_retry_at')->nullable()->after('processing_attempts');

            $table->index(['payment_attempt_id', 'received_at']);
            $table->index(['processing_status', 'next_retry_at']);
        });
    }

    public function down(): void
    {
        Schema::table('payment_webhook_events', function (Blueprint $table) {
            $table->dropIndex(['payment_attempt_id', 'received_at']);
            $table->dropIndex(['processing_status', 'next_retry_at']);
            $table->dropForeign(['payment_attempt_id']);
            $table->dropColumn([
                'payment_attempt_id',
                'provider_status',
                'processing_status',
                'payload',
                'processing_attempts',
                'next_retry_at',
            ]);
        });
    }
};

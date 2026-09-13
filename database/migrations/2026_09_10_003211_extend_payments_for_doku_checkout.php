<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('currency', 3)->default('IDR')->after('amount');
            $table->string('provider_request_id')->nullable()->after('provider');
            $table->string('provider_reference')->nullable()->after('provider_request_id');
            $table->text('checkout_url')->nullable()->after('external_reference');
            $table->timestamp('expires_at')->nullable()->after('checkout_url');
            $table->timestamp('failed_at')->nullable()->after('paid_at');
            $table->timestamp('expired_at')->nullable()->after('failed_at');
            $table->timestamp('refunded_at')->nullable()->after('expired_at');
            $table->timestamp('fulfilled_at')->nullable()->after('refunded_at');
            $table->text('fulfillment_failure')->nullable()->after('fulfilled_at');
            $table->unsignedInteger('boost_duration_hours')->nullable()->after('fulfillment_failure');

            $table->unique(['provider', 'provider_request_id']);
            $table->index(['status', 'expires_at']);
            $table->index(['product_id', 'type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['provider', 'provider_request_id']);
            $table->dropIndex(['status', 'expires_at']);
            $table->dropIndex(['product_id', 'type', 'created_at']);
            $table->dropColumn([
                'currency',
                'provider_request_id',
                'provider_reference',
                'checkout_url',
                'expires_at',
                'failed_at',
                'expired_at',
                'refunded_at',
                'fulfilled_at',
                'fulfillment_failure',
                'boost_duration_hours',
            ]);
        });
    }
};

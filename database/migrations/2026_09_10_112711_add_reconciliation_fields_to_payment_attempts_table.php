<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_attempts', function (Blueprint $table) {
            $table->longText('request_body')->nullable()->after('request_payload');
            $table->timestamp('first_requested_at')->nullable()->after('request_timestamp');
            $table->timestamp('last_reconciled_at')->nullable()->after('first_requested_at');
            $table->string('last_provider_status')->nullable()->after('last_reconciled_at');

            $table->index(['status', 'last_reconciled_at']);
        });
    }

    public function down(): void
    {
        Schema::table('payment_attempts', function (Blueprint $table) {
            $table->dropIndex(['status', 'last_reconciled_at']);
            $table->dropColumn([
                'request_body',
                'first_requested_at',
                'last_reconciled_at',
                'last_provider_status',
            ]);
        });
    }
};

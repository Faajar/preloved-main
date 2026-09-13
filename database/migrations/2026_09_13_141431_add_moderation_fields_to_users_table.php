<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedInteger('warning_count')->default(0)->after('role');
            $table->timestamp('suspended_until')->nullable()->after('warning_count');
            $table->text('suspension_reason')->nullable()->after('suspended_until');

            $table->index('suspended_until');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['suspended_until']);
            $table->dropColumn(['warning_count', 'suspended_until', 'suspension_reason']);
        });
    }
};

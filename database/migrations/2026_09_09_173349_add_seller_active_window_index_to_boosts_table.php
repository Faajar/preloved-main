<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boosts', function (Blueprint $table) {
            $table->index(['user_id', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::table('boosts', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'starts_at', 'ends_at']);
        });
    }
};

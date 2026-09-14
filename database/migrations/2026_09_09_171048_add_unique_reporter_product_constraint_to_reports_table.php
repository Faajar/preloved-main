<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->unique(['reporter_id', 'product_id']);
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex(['reporter_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->index(['reporter_id', 'product_id']);
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropUnique(['reporter_id', 'product_id']);
        });
    }
};

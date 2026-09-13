<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('reason');
            $table->text('description')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['product_id', 'status']);
            $table->index(['reporter_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};

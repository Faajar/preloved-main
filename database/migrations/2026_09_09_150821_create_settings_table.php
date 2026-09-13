<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('listing_fee_enabled')->default(false);
            $table->boolean('boost_fee_enabled')->default(false);
            $table->unsignedInteger('listing_fee')->default(3000);
            $table->unsignedInteger('boost_fee')->default(3000);
            $table->unsignedInteger('boost_duration_hours')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('location_columns', function (Blueprint $table) {
            $table->id();
            $table->char('level', 1); // A-L
            $table->tinyInteger('height'); // 1-6
            $table->string('current_sku')->nullable(); // Tracks assigned SKU
            $table->timestamps();

            $table->unique(['level', 'height']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_columns');
    }
};

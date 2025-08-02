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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->char('level', 1); // A-L
            $table->tinyInteger('height'); // 1-6
            $table->tinyInteger('depth'); // 1-50
            $table->foreignId('product_id')->nullable()->constrained();
            $table->boolean('reserved')->default(false);
            $table->timestamps();

            $table->unique(['level', 'height', 'depth']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};

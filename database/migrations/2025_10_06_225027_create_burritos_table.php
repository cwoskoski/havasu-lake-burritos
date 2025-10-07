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
        Schema::create('burritos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->json('ingredient_selections');
            $table->decimal('total_calories', 6, 1)->nullable();
            $table->decimal('total_cost', 8, 2)->nullable();
            $table->text('special_instructions')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('burritos');
    }
};

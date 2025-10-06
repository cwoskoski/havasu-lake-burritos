<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredient_weeks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->date('week_start'); // Monday of the week
            $table->decimal('price_per_oz', 8, 2);
            $table->integer('quantity_available_oz')->nullable();
            $table->integer('quantity_used_oz')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->text('special_notes')->nullable();
            $table->timestamps();

            $table->unique(['ingredient_id', 'week_start']);
            $table->index(['week_start', 'is_featured']);
            $table->index('week_start');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredient_weeks');
    }
};

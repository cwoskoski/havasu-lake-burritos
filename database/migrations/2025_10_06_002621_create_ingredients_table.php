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
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category');
            $table->text('description')->nullable();
            $table->decimal('standard_portion_oz', 5, 2);
            $table->decimal('calories_per_oz', 5, 1);
            $table->json('allergens')->nullable();
            $table->json('dietary_info')->nullable();
            $table->string('color_hex', 7)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_premium')->default(false);
            $table->timestamps();

            $table->index(['category', 'is_active', 'sort_order']);
            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};

<?php

use App\Enums\IngredientCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('ingredients');

        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->enum('category', IngredientCategory::values());
            $table->text('description')->nullable();
            $table->decimal('standard_portion_oz', 5, 2)->nullable();
            $table->decimal('calories_per_oz', 5, 1)->nullable();
            $table->json('allergens')->nullable();
            $table->json('dietary_info')->nullable(); // vegan, gluten_free, etc
            $table->string('color_hex', 7)->nullable(); // for UI theming
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category', 'sort_order']);
            $table->index(['category', 'is_active']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};

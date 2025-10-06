<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_schedules', function (Blueprint $table) {
            $table->id();
            $table->date('production_date');
            $table->enum('day_of_week', ['saturday', 'sunday']);
            $table->integer('max_burritos');
            $table->integer('burritos_ordered')->default(0);
            $table->time('order_cutoff_time')->default('18:00:00');
            $table->time('pickup_start_time')->default('11:00:00');
            $table->time('pickup_end_time')->default('16:00:00');
            $table->boolean('is_active')->default(true);
            $table->text('special_notes')->nullable();
            $table->timestamps();

            $table->unique('production_date');
            $table->index(['production_date', 'is_active']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_schedules');
    }
};

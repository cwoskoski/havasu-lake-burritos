<?php

use App\Enums\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 20)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('production_schedule_id')->constrained('production_schedules')->cascadeOnDelete();

            // Customer contact info (for guest orders)
            $table->string('customer_name', 100);
            $table->string('customer_phone', 20);
            $table->string('customer_email', 100)->nullable();

            // Order details
            $table->enum('status', OrderStatus::values())->default(OrderStatus::PENDING->value);
            $table->decimal('subtotal', 8, 2);
            $table->decimal('tax_amount', 8, 2)->default(0);
            $table->decimal('total_amount', 8, 2);

            // Timing
            $table->timestamp('pickup_time')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('prepared_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Kitchen & admin
            $table->text('special_instructions')->nullable();
            $table->text('admin_notes')->nullable();
            $table->boolean('kitchen_printed')->default(false);
            $table->timestamp('kitchen_printed_at')->nullable();

            $table->timestamps();

            $table->index(['production_schedule_id', 'status']);
            $table->index(['customer_phone', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index('order_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

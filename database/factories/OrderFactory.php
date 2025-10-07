<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'order_number' => $this->generateOrderNumber(),
            'status' => OrderStatus::PENDING,
            'guest_name' => null,
            'guest_phone' => null,
            'total_amount' => $this->faker->numberBetween(1200, 3600), // $12-$36
            'tax_amount' => fn (array $attributes) => (int) round($attributes['total_amount'] * 0.08), // 8% tax
            'pickup_time' => $this->getNextWeekendPickupTime(),
            'customer_notes' => $this->faker->optional(0.3)->sentence(),
            'admin_notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Create a guest order (no user account).
     */
    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'guest_name' => $this->faker->name(),
            'guest_phone' => $this->generatePhoneNumber(),
        ]);
    }

    /**
     * Create an order with a specific status.
     */
    public function withStatus(OrderStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
            'updated_at' => $this->getStatusUpdateTime($status),
        ]);
    }

    /**
     * Create an order for a specific pickup time.
     */
    public function forPickup(Carbon $pickupTime): static
    {
        return $this->state(fn (array $attributes) => [
            'pickup_time' => $pickupTime,
        ]);
    }

    /**
     * Create an order with customer notes.
     */
    public function withNotes(string $customerNotes, ?string $adminNotes = null): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_notes' => $customerNotes,
            'admin_notes' => $adminNotes,
        ]);
    }

    /**
     * Create a high-value order for testing.
     */
    public function highValue(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_amount' => $this->faker->numberBetween(4800, 7200), // $48-$72
            'tax_amount' => fn (array $attr) => (int) round($attr['total_amount'] * 0.08),
        ]);
    }

    /**
     * Create an order for load testing.
     */
    public function loadTest(): static
    {
        return $this->sequence(
            fn ($sequence) => [
                'order_number' => "LOAD{$sequence->index}",
                'total_amount' => 1200, // Standard $12 burrito
                'tax_amount' => 96, // $0.96 tax
                'pickup_time' => $this->getSequentialPickupTime($sequence->index),
            ]
        );
    }

    /**
     * Create a completed order for testing order history.
     */
    public function completed(): static
    {
        return $this->withStatus(OrderStatus::COMPLETED)
            ->state(fn (array $attributes) => [
                'pickup_time' => Carbon::now()->subHours($this->faker->numberBetween(1, 48)),
                'created_at' => Carbon::now()->subHours($this->faker->numberBetween(49, 168)), // 2-7 days ago
            ]);
    }

    /**
     * Create a cancelled order for testing.
     */
    public function cancelled(): static
    {
        return $this->withStatus(OrderStatus::CANCELLED)
            ->state(fn (array $attributes) => [
                'admin_notes' => 'Cancelled by customer request',
                'updated_at' => Carbon::now()->subHours($this->faker->numberBetween(1, 24)),
            ]);
    }

    /**
     * Create an order for this weekend.
     */
    public function thisWeekend(): static
    {
        $saturday = Carbon::now()->next(Carbon::SATURDAY)->setHour(11);
        $sunday = Carbon::now()->next(Carbon::SUNDAY)->setHour(12);

        return $this->forPickup($this->faker->randomElement([$saturday, $sunday]));
    }

    /**
     * Create an order for next weekend.
     */
    public function nextWeekend(): static
    {
        $saturday = Carbon::now()->addWeek()->next(Carbon::SATURDAY)->setHour(11);
        $sunday = Carbon::now()->addWeek()->next(Carbon::SUNDAY)->setHour(12);

        return $this->forPickup($this->faker->randomElement([$saturday, $sunday]));
    }

    /**
     * Generate a unique order number.
     */
    private function generateOrderNumber(): string
    {
        // Format: YYYYMMDD-XXXX (date + 4 random digits)
        $date = Carbon::now()->format('Ymd');
        $random = str_pad((string) $this->faker->numberBetween(1000, 9999), 4, '0', STR_PAD_LEFT);

        return "{$date}-{$random}";
    }

    /**
     * Get the next available weekend pickup time.
     */
    private function getNextWeekendPickupTime(): Carbon
    {
        $saturday = Carbon::now()->next(Carbon::SATURDAY);
        $sunday = Carbon::now()->next(Carbon::SUNDAY);

        // Random time between 10 AM and 4 PM on weekend
        $weekend = $this->faker->randomElement([$saturday, $sunday]);
        $hour = $this->faker->numberBetween(10, 16);
        $minute = $this->faker->randomElement([0, 15, 30, 45]);

        return $weekend->setHour($hour)->setMinute($minute)->setSecond(0);
    }

    /**
     * Get appropriate update time based on order status.
     */
    private function getStatusUpdateTime(OrderStatus $status): Carbon
    {
        return match ($status) {
            OrderStatus::PENDING => now(),
            OrderStatus::CONFIRMED => now()->addMinutes($this->faker->numberBetween(5, 30)),
            OrderStatus::PREPARING => now()->addMinutes($this->faker->numberBetween(60, 120)),
            OrderStatus::READY => now()->addMinutes($this->faker->numberBetween(150, 180)),
            OrderStatus::COMPLETED => now()->addMinutes($this->faker->numberBetween(180, 200)),
            OrderStatus::CANCELLED => now()->addMinutes($this->faker->numberBetween(10, 60)),
        };
    }

    /**
     * Generate a realistic phone number.
     */
    private function generatePhoneNumber(): string
    {
        $areaCode = $this->faker->randomElement(['602', '623', '480', '520', '928']);
        $exchange = $this->faker->numberBetween(200, 999);
        $number = $this->faker->numberBetween(1000, 9999);

        return "+1{$areaCode}{$exchange}{$number}";
    }

    /**
     * Generate sequential pickup times for load testing.
     */
    private function getSequentialPickupTime(int $index): Carbon
    {
        $baseTime = Carbon::now()->next(Carbon::SATURDAY)->setHour(10)->setMinute(0);
        $minutesOffset = $index * 15; // 15 minutes apart

        return $baseTime->addMinutes($minutesOffset);
    }
}

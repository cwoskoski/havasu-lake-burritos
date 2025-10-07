<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Burrito;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Burrito>
 */
class BurritoFactory extends Factory
{
    protected $model = Burrito::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'price' => 1200, // $12.00
            'custom_instructions' => $this->faker->optional(0.2)->sentence(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Create a burrito with custom instructions.
     */
    public function withInstructions(string $instructions): static
    {
        return $this->state(fn (array $attributes) => [
            'custom_instructions' => $instructions,
        ]);
    }

    /**
     * Create a burrito with specific price.
     */
    public function withPrice(int $priceInCents): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => $priceInCents,
        ]);
    }

    /**
     * Create a premium burrito (higher price).
     */
    public function premium(): static
    {
        return $this->withPrice(1500); // $15.00
    }

    /**
     * Create a basic burrito (lower price).
     */
    public function basic(): static
    {
        return $this->withPrice(1000); // $10.00
    }

    /**
     * Create a burrito for an existing order.
     */
    public function forOrder(Order $order): static
    {
        return $this->state(fn (array $attributes) => [
            'order_id' => $order->id,
        ]);
    }

    /**
     * Create a burrito with common custom instructions.
     */
    public function withCommonInstructions(): static
    {
        $instructions = [
            'Extra spicy please',
            'Light on the rice',
            'No beans please',
            'Extra cheese',
            'Well done',
            'Extra salsa on the side',
            'Make it mild',
            'Double protein',
        ];

        return $this->withInstructions($this->faker->randomElement($instructions));
    }

    /**
     * Create multiple burritos for load testing.
     */
    public function loadTest(): static
    {
        return $this->sequence(
            fn ($sequence) => [
                'price' => 1200,
                'custom_instructions' => $sequence->index % 3 === 0 ? 'Load test burrito' : null,
            ]
        );
    }
}

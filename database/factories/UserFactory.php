<?php

namespace Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'phone' => $this->generatePhoneNumber(),
            'phone_verified_at' => null,
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'sms_notifications' => true,
            'marketing_sms' => false,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create a user with verified phone number.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone_verified_at' => Carbon::now(),
            'phone' => $this->generatePhoneNumber(),
        ]);
    }

    /**
     * Create a user with unverified phone number.
     */
    public function phoneUnverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone_verified_at' => null,
            'phone' => $this->generatePhoneNumber(),
        ]);
    }

    /**
     * Create a user without a phone number.
     */
    public function withoutPhone(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone' => null,
            'phone_verified_at' => null,
        ]);
    }

    /**
     * Create a user with specific phone number.
     */
    public function withPhone(string $phone, bool $verified = true): static
    {
        return $this->state(fn (array $attributes) => [
            'phone' => $phone,
            'phone_verified_at' => $verified ? Carbon::now() : null,
        ]);
    }

    /**
     * Create a user who opts into marketing SMS.
     */
    public function wantsMarketing(): static
    {
        return $this->state(fn (array $attributes) => [
            'marketing_sms' => true,
            'sms_notifications' => true,
        ]);
    }

    /**
     * Create a user who opts out of all SMS.
     */
    public function noSms(): static
    {
        return $this->state(fn (array $attributes) => [
            'sms_notifications' => false,
            'marketing_sms' => false,
        ]);
    }

    /**
     * Create a user with a specific email for testing.
     */
    public function withEmail(string $email): static
    {
        return $this->state(fn (array $attributes) => [
            'email' => $email,
        ]);
    }

    /**
     * Create a customer who has placed orders before.
     */
    public function returningCustomer(): static
    {
        return $this->verified()
            ->wantsMarketing()
            ->state(fn (array $attributes) => [
                'created_at' => Carbon::now()->subMonths(3),
            ]);
    }

    /**
     * Create a new customer (just signed up).
     */
    public function newCustomer(): static
    {
        return $this->verified()
            ->state(fn (array $attributes) => [
                'created_at' => Carbon::now()->subMinutes(5),
                'marketing_sms' => false, // New customers typically don't opt-in initially
            ]);
    }

    /**
     * Create a test user with predictable data.
     */
    public function testUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Test User',
            'email' => 'test@havasu-burritos.test',
            'phone' => '+15551234567',
            'phone_verified_at' => Carbon::now(),
            'password' => Hash::make('password123'),
        ]);
    }

    /**
     * Create an admin user for testing.
     */
    public function admin(): static
    {
        return $this->verified()
            ->state(fn (array $attributes) => [
                'name' => 'Admin User',
                'email' => 'admin@havasu-burritos.test',
                'is_admin' => true, // Assuming you have an admin flag
            ]);
    }

    /**
     * Create users for load testing.
     */
    public function loadTest(): static
    {
        return $this->verified()
            ->sequence(
                fn ($sequence) => [
                    'name' => "Load Test User {$sequence->index}",
                    'email' => "loadtest{$sequence->index}@havasu-burritos.test",
                    'phone' => $this->generateSequentialPhone($sequence->index),
                ]
            );
    }

    /**
     * Generate a realistic US phone number.
     */
    private function generatePhoneNumber(): string
    {
        // Generate a US phone number in E.164 format
        $areaCode = $this->faker->randomElement([
            '555', '602', '623', '480', '520', '928', // Arizona area codes + test
        ]);

        $exchange = $this->faker->numberBetween(200, 999);
        $number = $this->faker->numberBetween(1000, 9999);

        return "+1{$areaCode}{$exchange}{$number}";
    }

    /**
     * Generate sequential phone numbers for load testing.
     */
    private function generateSequentialPhone(int $index): string
    {
        $baseNumber = 5551000000 + $index;

        return "+1{$baseNumber}";
    }
}

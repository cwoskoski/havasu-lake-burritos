<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Unit tests for the User model with burrito business logic.
 * Tests phone verification, SMS preferences, and ordering capabilities.
 */
class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_created_with_valid_data(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+15551234567',
        ]);

        expect($user->name)->toBe('John Doe');
        expect($user->email)->toBe('john@example.com');
        expect($user->phone)->toBe('+15551234567');
        expect($user->exists)->toBeTrue();
    }

    public function test_user_password_is_properly_hashed(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
        ]);

        expect(Hash::check('password123', $user->password))->toBeTrue();
        expect($user->password)->not->toBe('password123'); // Should be hashed
    }

    public function test_user_phone_verification_status(): void
    {
        // Unverified phone
        $unverifiedUser = User::factory()->phoneUnverified()->create();
        expect($unverifiedUser->phone_verified_at)->toBeNull();
        expect($unverifiedUser->isPhoneVerified())->toBeFalse();

        // Verified phone
        $verifiedUser = User::factory()->verified()->create();
        expect($verifiedUser->phone_verified_at)->not->toBeNull();
        expect($verifiedUser->isPhoneVerified())->toBeTrue();
    }

    public function test_user_phone_number_format_validation(): void
    {
        $validPhones = [
            '+15551234567',
            '+14801234567',
            '+16021234567',
        ];

        foreach ($validPhones as $phone) {
            $user = User::factory()->create(['phone' => $phone]);
            expect($user->phone)->toHaveValidPhoneNumber();
        }
    }

    public function test_user_sms_notification_preferences(): void
    {
        // Default SMS preferences
        $user = User::factory()->create();
        expect($user->sms_notifications)->toBeTrue();
        expect($user->marketing_sms)->toBeFalse();

        // User who wants marketing
        $marketingUser = User::factory()->wantsMarketing()->create();
        expect($marketingUser->sms_notifications)->toBeTrue();
        expect($marketingUser->marketing_sms)->toBeTrue();

        // User who opts out of SMS
        $noSmsUser = User::factory()->noSms()->create();
        expect($noSmsUser->sms_notifications)->toBeFalse();
        expect($noSmsUser->marketing_sms)->toBeFalse();
    }

    public function test_user_factory_states(): void
    {
        // Test verified user factory
        $verifiedUser = User::factory()->verified()->create();
        expect($verifiedUser->phone_verified_at)->not->toBeNull();
        expect($verifiedUser->phone)->not->toBeNull();

        // Test user without phone
        $noPhoneUser = User::factory()->withoutPhone()->create();
        expect($noPhoneUser->phone)->toBeNull();
        expect($noPhoneUser->phone_verified_at)->toBeNull();

        // Test returning customer
        $returningCustomer = User::factory()->returningCustomer()->create();
        expect($returningCustomer->phone_verified_at)->not->toBeNull();
        expect($returningCustomer->marketing_sms)->toBeTrue();
        expect($returningCustomer->created_at)->toBeBefore(Carbon::now()->subMonth());

        // Test new customer
        $newCustomer = User::factory()->newCustomer()->create();
        expect($newCustomer->phone_verified_at)->not->toBeNull();
        expect($newCustomer->marketing_sms)->toBeFalse();
        expect($newCustomer->created_at)->toBeAfter(Carbon::now()->subMinutes(10));
    }

    public function test_user_can_have_specific_phone_number(): void
    {
        $phone = '+16025551234';
        $user = User::factory()->withPhone($phone, true)->create();

        expect($user->phone)->toBe($phone);
        expect($user->phone_verified_at)->not->toBeNull();

        // Test unverified phone
        $unverifiedUser = User::factory()->withPhone($phone, false)->create();
        expect($unverifiedUser->phone)->toBe($phone);
        expect($unverifiedUser->phone_verified_at)->toBeNull();
    }

    public function test_user_can_have_specific_email(): void
    {
        $email = 'specific@example.com';
        $user = User::factory()->withEmail($email)->create();

        expect($user->email)->toBe($email);
    }

    public function test_test_user_factory_creates_predictable_data(): void
    {
        $testUser = User::factory()->testUser()->create();

        expect($testUser->name)->toBe('Test User');
        expect($testUser->email)->toBe('test@havasu-burritos.test');
        expect($testUser->phone)->toBe('+15551234567');
        expect($testUser->phone_verified_at)->not->toBeNull();
        expect(Hash::check('password123', $testUser->password))->toBeTrue();
    }

    public function test_admin_user_factory(): void
    {
        $adminUser = User::factory()->admin()->create();

        expect($adminUser->name)->toBe('Admin User');
        expect($adminUser->email)->toBe('admin@havasu-burritos.test');
        expect($adminUser->is_admin)->toBeTrue();
        expect($adminUser->phone_verified_at)->not->toBeNull();
    }

    public function test_load_test_user_factory(): void
    {
        $loadTestUsers = User::factory()->loadTest()->count(5)->create();

        expect($loadTestUsers)->toHaveCount(5);

        foreach ($loadTestUsers as $index => $user) {
            expect($user->name)->toBe("Load Test User {$index}");
            expect($user->email)->toBe("loadtest{$index}@havasu-burritos.test");
            expect($user->phone_verified_at)->not->toBeNull();
        }
    }

    public function test_user_phone_verification_business_logic(): void
    {
        $user = User::factory()->phoneUnverified()->create();

        // User should not be able to place orders without phone verification
        expect($user->canPlaceOrders())->toBeFalse();

        // After phone verification
        $user->phone_verified_at = Carbon::now();
        expect($user->canPlaceOrders())->toBeTrue();
    }

    public function test_user_ordering_permissions(): void
    {
        // User with verified phone can order
        $verifiedUser = User::factory()->verified()->create();
        expect($verifiedUser->canPlaceOrders())->toBeTrue();

        // User without phone cannot order
        $noPhoneUser = User::factory()->withoutPhone()->create();
        expect($noPhoneUser->canPlaceOrders())->toBeFalse();

        // User with unverified phone cannot order
        $unverifiedUser = User::factory()->phoneUnverified()->create();
        expect($unverifiedUser->canPlaceOrders())->toBeFalse();
    }

    public function test_user_sms_notification_eligibility(): void
    {
        // User with verified phone and SMS enabled should receive notifications
        $user = User::factory()->verified()->create(['sms_notifications' => true]);
        expect($user->canReceiveSms())->toBeTrue();

        // User who opted out of SMS should not receive notifications
        $noSmsUser = User::factory()->verified()->noSms()->create();
        expect($noSmsUser->canReceiveSms())->toBeFalse();

        // User without verified phone should not receive SMS
        $unverifiedUser = User::factory()->phoneUnverified()->create();
        expect($unverifiedUser->canReceiveSms())->toBeFalse();
    }

    public function test_user_marketing_sms_eligibility(): void
    {
        // User who opted into marketing should receive marketing SMS
        $marketingUser = User::factory()->wantsMarketing()->create();
        expect($marketingUser->canReceiveMarketingSms())->toBeTrue();

        // Regular user should not receive marketing SMS
        $regularUser = User::factory()->verified()->create();
        expect($regularUser->canReceiveMarketingSms())->toBeFalse();

        // User who opted out of all SMS should not receive marketing
        $noSmsUser = User::factory()->noSms()->create();
        expect($noSmsUser->canReceiveMarketingSms())->toBeFalse();
    }

    public function test_user_profile_completeness(): void
    {
        // Complete profile
        $completeUser = User::factory()->verified()->create();
        expect($completeUser->hasCompleteProfile())->toBeTrue();

        // Incomplete profile (missing phone)
        $incompleteUser = User::factory()->withoutPhone()->create();
        expect($incompleteUser->hasCompleteProfile())->toBeFalse();

        // Incomplete profile (unverified phone)
        $unverifiedUser = User::factory()->phoneUnverified()->create();
        expect($unverifiedUser->hasCompleteProfile())->toBeFalse();
    }

    public function test_user_data_privacy(): void
    {
        $user = User::factory()->create();

        // Sensitive fields should be hidden in serialization
        $serialized = $user->toArray();
        expect($serialized)->not->toHaveKey('password');
        expect($serialized)->not->toHaveKey('remember_token');
    }

    public function test_user_timezone_handling(): void
    {
        // All users in Arizona should handle timezone correctly
        $user = User::factory()->create();

        expect($user->created_at->timezone->getName())->toBe('UTC');

        // When converting to user's timezone (Arizona)
        $arizonaTime = $user->created_at->setTimezone('America/Phoenix');
        expect($arizonaTime->timezone->getName())->toBe('America/Phoenix');
    }

    public function test_user_performance_with_large_datasets(): void
    {
        // Create many users for performance testing
        User::factory()->count(1000)->create();

        $this->assertQueryPerformance(function () {
            $verifiedUsers = User::where('phone_verified_at', '!=', null)->count();
            expect($verifiedUsers)->toBeGreaterThan(0);
        }, 3); // Should use max 3 queries

        $this->assertMemoryUsage(10); // Should use less than 10MB
    }

    public function test_user_email_uniqueness(): void
    {
        $email = 'unique@example.com';
        User::factory()->create(['email' => $email]);

        // Should not be able to create another user with same email
        $this->expectException(\Illuminate\Database\QueryException::class);
        User::factory()->create(['email' => $email]);
    }

    public function test_user_authentication_attributes(): void
    {
        $user = User::factory()->create();

        // Test that authentication fields are properly set
        expect($user->email_verified_at)->not->toBeNull();
        expect($user->password)->not->toBeEmpty();
        expect($user->remember_token)->not->toBeEmpty();
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\SmsVerification;
use App\Models\User;
use App\Services\SmsService;
use Carbon\Carbon;
use Exception;
use InvalidArgumentException;

/**
 * TDD Tests for SMS Communication System
 * Business Rules: Phone verification, order notifications, rate limiting
 */
describe('Phone Number Verification', function () {
    beforeEach(function () {
        mockTwilioService();
        $this->smsService = new SmsService;
        $this->phoneNumber = '+15551234567';
    });

    it('generates 6-digit verification codes', function () {
        $code = $this->smsService->generateVerificationCode();

        expect($code)->toMatch('/^\d{6}$/');
        expect(strlen($code))->toBe(6);
        expect($code)->toBeGreaterThanOrEqual(100000);
        expect($code)->toBeLessThanOrEqual(999999);
    });

    it('creates verification records with expiration', function () {
        $verification = $this->smsService->createVerification($this->phoneNumber);

        expect($verification->phone_number)->toBe($this->phoneNumber);
        expect($verification->verification_code)->toMatch('/^\d{6}$/');
        expect($verification->expires_at)->toBeInstanceOf(Carbon::class);
        expect($verification->expires_at->isFuture())->toBeTrue();
        expect($verification->is_verified)->toBeFalse();
    });

    it('enforces 10-minute expiration window', function () {
        $verification = $this->smsService->createVerification($this->phoneNumber);
        $expectedExpiry = Carbon::now()->addMinutes(10);

        expect((int) abs($verification->expires_at->diffInMinutes(Carbon::now())))->toBeGreaterThanOrEqual(9);
        expect((int) abs($verification->expires_at->diffInMinutes(Carbon::now())))->toBeLessThanOrEqual(10);
        // Expiry should be approximately 10 minutes from now (allowing for execution time)
        expect($verification->expires_at->diffInMinutes($expectedExpiry, false))->toBeLessThan(1);
    });

    it('validates verification codes correctly', function () {
        $verification = $this->smsService->createVerification($this->phoneNumber);
        $correctCode = $verification->verification_code;
        $wrongCode = '000000';

        expect($this->smsService->verifyCode($this->phoneNumber, $correctCode))->toBeTrue();
        expect($this->smsService->verifyCode($this->phoneNumber, $wrongCode))->toBeFalse();
    });

    it('marks verification as used after successful verification', function () {
        $verification = $this->smsService->createVerification($this->phoneNumber);
        $code = $verification->verification_code;

        $this->smsService->verifyCode($this->phoneNumber, $code);

        expect($verification->is_verified)->toBeTrue();
        expect($verification->verified_at)->toBeInstanceOf(Carbon::class);
    });

    it('prevents reuse of verification codes', function () {
        $verification = $this->smsService->createVerification($this->phoneNumber);
        $code = $verification->verification_code;

        // First use should work
        $firstResult = $this->smsService->verifyCode($this->phoneNumber, $code);
        expect($firstResult)->toBeTrue();

        // Verification should now be marked as used
        expect($verification->is_verified)->toBeTrue();

        // Second use should fail
        $secondResult = $this->smsService->verifyCode($this->phoneNumber, $code);
        expect($secondResult)->toBeFalse();
    });
});

describe('Rate Limiting Protection', function () {
    beforeEach(function () {
        mockTwilioService();
        $this->smsService = new SmsService;
        $this->phoneNumber = '+15551234567';
    });

    it('allows up to 3 attempts per 10 minutes', function () {
        // First 3 attempts should succeed
        expect($this->smsService->canSendVerification($this->phoneNumber))->toBeTrue();
        $this->smsService->sendVerificationCode($this->phoneNumber);

        expect($this->smsService->canSendVerification($this->phoneNumber))->toBeTrue();
        $this->smsService->sendVerificationCode($this->phoneNumber);

        expect($this->smsService->canSendVerification($this->phoneNumber))->toBeTrue();
        $this->smsService->sendVerificationCode($this->phoneNumber);

        // 4th attempt should be blocked
        expect($this->smsService->canSendVerification($this->phoneNumber))->toBeFalse();
    });

    it('provides time until next attempt allowed', function () {
        // Send 3 messages to hit rate limit
        for ($i = 0; $i < 3; $i++) {
            $this->smsService->sendVerificationCode($this->phoneNumber);
        }

        $waitTime = $this->smsService->getTimeUntilNextAttempt($this->phoneNumber);

        expect($waitTime)->toBeInstanceOf(Carbon::class);
        expect($waitTime->isFuture())->toBeTrue();
        expect($waitTime->diffInMinutes(Carbon::now()))->toBeLessThanOrEqual(10);
    });

    it('resets rate limit after time window', function () {
        // Hit rate limit
        for ($i = 0; $i < 3; $i++) {
            $this->smsService->sendVerificationCode($this->phoneNumber);
        }

        expect($this->smsService->canSendVerification($this->phoneNumber))->toBeFalse();

        // Travel 11 minutes into future
        Carbon::setTestNow(Carbon::now()->addMinutes(11));

        expect($this->smsService->canSendVerification($this->phoneNumber))->toBeTrue();

        Carbon::setTestNow(); // Reset
    });

    it('prevents spam with progressive delays', function () {
        $initialDelay = $this->smsService->getInitialDelay();
        expect($initialDelay)->toBe(0); // No delay for first attempt

        // After hitting rate limit once
        for ($i = 0; $i < 3; $i++) {
            $this->smsService->sendVerificationCode($this->phoneNumber);
        }

        $penaltyDelay = $this->smsService->getPenaltyDelay($this->phoneNumber);
        expect($penaltyDelay)->toBeGreaterThan(10); // Progressive penalty
    });
});

describe('Order Notification SMS', function () {
    beforeEach(function () {
        mockTwilioService();
        $this->smsService = new SmsService;
        $this->phoneNumber = '+15551234567';
        $this->orderNumber = 'HLB-20250106-ABCD';
    });

    it('sends order confirmation notifications', function () {
        $result = $this->smsService->sendOrderConfirmation(
            $this->phoneNumber,
            $this->orderNumber,
            Carbon::now()->addHour()
        );

        expect($result)->toBeTrue();
    });

    it('sends ready for pickup notifications', function () {
        $result = $this->smsService->sendReadyNotification(
            $this->phoneNumber,
            $this->orderNumber,
            'Havasu Lake Burritos - 123 Lake Drive'
        );

        expect($result)->toBeTrue();
    });

    it('formats order notification messages correctly', function () {
        $confirmationMessage = $this->smsService->formatOrderConfirmation(
            $this->orderNumber,
            Carbon::now()->addHour()
        );

        expect($confirmationMessage)->toContain($this->orderNumber);
        expect($confirmationMessage)->toContain('confirmed');
        expect($confirmationMessage)->toContain('ready');

        $readyMessage = $this->smsService->formatReadyNotification(
            $this->orderNumber,
            'Havasu Lake Burritos'
        );

        expect($readyMessage)->toContain($this->orderNumber);
        expect($readyMessage)->toContain('ready');
        expect($readyMessage)->toContain('pickup');
    });

    it('includes mobile-friendly formatting', function () {
        $message = $this->smsService->formatOrderConfirmation(
            'HLB-20250106-ABCD',
            Carbon::create(2025, 1, 6, 14, 30)
        );

        // Should be under 160 characters for single SMS
        expect(strlen($message))->toBeLessThan(160);

        // Should include key information
        expect($message)->toContain('HLB-20250106-ABCD');
        expect($message)->toContain('2:30 PM');
    });

    it('handles notification delivery failures gracefully', function () {
        // Mock a failure scenario
        $this->smsService->setMockFailure(true);

        $result = $this->smsService->sendOrderConfirmation(
            '+15551234567', // Use valid format for test
            $this->orderNumber,
            Carbon::now()->addHour()
        );

        expect($result)->toBeFalse();

        // Should log the failure for retry
        $failures = $this->smsService->getFailedNotifications();
        expect($failures)->toHaveCount(1);
    });
});

describe('User Phone Verification Integration', function () {
    beforeEach(function () {
        mockTwilioService();
        $this->smsService = new SmsService;
        $this->user = createVerifiedUser(['phone' => null, 'phone_verified_at' => null]);
        $this->phoneNumber = '+15551234567';
    });

    it('integrates with user phone verification workflow', function () {
        // Send verification code
        $verification = $this->smsService->sendUserVerification($this->user, $this->phoneNumber);

        expect($verification)->toBeInstanceOf(SmsVerification::class);
        expect($verification->user_id)->toBe($this->user->id);
        expect($verification->phone_number)->toBe($this->phoneNumber);
    });

    it('updates user phone when verification succeeds', function () {
        $verification = $this->smsService->sendUserVerification($this->user, $this->phoneNumber);
        $code = $verification->verification_code;

        $result = $this->smsService->verifyUserPhone($this->user, $this->phoneNumber, $code);

        expect($result)->toBeTrue();
        expect($this->user->fresh()->phone)->toBe($this->phoneNumber);
        expect($this->user->fresh()->phone_verified_at)->toBeInstanceOf(Carbon::class);
    });

    it('prevents phone number hijacking', function () {
        $otherUser = createVerifiedUser();

        // Try to verify a phone already associated with another user
        expect(fn () => $this->smsService->sendUserVerification($this->user, $otherUser->phone))
            ->toThrow(InvalidArgumentException::class);
    });

    it('allows phone number updates for same user', function () {
        $this->user->update(['phone' => $this->phoneNumber, 'phone_verified_at' => now()]);
        $newPhone = '+15559876543';

        // Should allow same user to update their phone
        expect(fn () => $this->smsService->sendUserVerification($this->user, $newPhone))
            ->not->toThrow(InvalidArgumentException::class);
    });
});

describe('SMS Template Management', function () {
    beforeEach(function () {
        $this->smsService = new SmsService;
    });

    it('provides customizable message templates', function () {
        $templates = $this->smsService->getMessageTemplates();

        expect($templates)->toHaveKey('verification');
        expect($templates)->toHaveKey('order_confirmation');
        expect($templates)->toHaveKey('order_ready');
        expect($templates)->toHaveKey('order_cancelled');

        // Each template should have required placeholders
        expect($templates['verification'])->toContain('{code}');
        expect($templates['order_confirmation'])->toContain('{order_number}');
        expect($templates['order_ready'])->toContain('{order_number}');
    });

    it('applies business branding to messages', function () {
        $message = $this->smsService->formatVerificationCode('123456');

        expect($message)->toContain('Havasu Lake Burritos');
        expect($message)->toContain('123456');
        expect($message)->toContain('expires');
    });

    it('includes opt-out information in marketing messages', function () {
        $marketingMessage = $this->smsService->formatMarketingMessage('Weekly specials available!');

        expect($marketingMessage)->toContain('STOP');
        expect($marketingMessage)->toContain('opt out');
    });

    it('formats messages for mobile display', function () {
        $longOrderNumber = 'HLB-20250106-VERY-LONG-ORDER-NUMBER';
        $message = $this->smsService->formatOrderConfirmation($longOrderNumber, Carbon::now());

        // Should abbreviate if needed
        expect(strlen($message))->toBeLessThan(160);
        expect($message)->toContain('HLB-');
    });
});

describe('Error Handling and Resilience', function () {
    beforeEach(function () {
        $this->smsService = new SmsService;
    });

    it('handles Twilio API failures gracefully', function () {
        $this->smsService->setMockFailure(true);

        expect(fn () => $this->smsService->sendVerificationCode('+15551234567'))
            ->not->toThrow(Exception::class);

        // Should return false instead of throwing
        $result = $this->smsService->sendVerificationCode('+15551234567');
        expect($result)->toBeFalse();
    });

    it('queues failed messages for retry', function () {
        $this->smsService->setMockFailure(true);

        $this->smsService->sendOrderConfirmation('+15551234567', 'HLB-TEST', Carbon::now());

        $queuedMessages = $this->smsService->getQueuedRetries();
        expect($queuedMessages)->toHaveCount(1);
        expect($queuedMessages[0]['type'])->toBe('order_confirmation');
    });

    it('validates phone numbers before sending', function () {
        expect(fn () => $this->smsService->sendVerificationCode('invalid-phone'))
            ->toThrow(InvalidArgumentException::class);

        expect(fn () => $this->smsService->sendVerificationCode('+1555123456789')) // Too long
            ->toThrow(InvalidArgumentException::class);
    });

    it('provides detailed error information for debugging', function () {
        $this->smsService->setMockFailure(true);

        $this->smsService->sendVerificationCode('+15551234567');

        $lastError = $this->smsService->getLastError();
        expect($lastError)->toHaveKey('error_code');
        expect($lastError)->toHaveKey('error_message');
        expect($lastError)->toHaveKey('timestamp');
        expect($lastError)->toHaveKey('phone_number');
    });
});

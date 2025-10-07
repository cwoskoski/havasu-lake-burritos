<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SmsVerification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class SmsService
{
    private bool $mockFailure = false;

    private array $failedNotifications = [];

    private array $queuedRetries = [];

    private ?array $lastError = null;

    private array $mockVerifications = []; // In-memory storage for testing

    private const RATE_LIMIT_ATTEMPTS = 3;

    private const RATE_LIMIT_WINDOW_MINUTES = 10;

    private const VERIFICATION_EXPIRY_MINUTES = 10;

    // === VERIFICATION CODE MANAGEMENT ===

    /**
     * Generate a 6-digit verification code.
     */
    public function generateVerificationCode(): string
    {
        return str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new SMS verification record.
     */
    public function createVerification(string $phoneNumber, ?int $userId = null): SmsVerification
    {
        $this->validatePhoneNumber($phoneNumber);

        $verification = new SmsVerification([
            'user_id' => $userId,
            'phone_number' => $phoneNumber,
            'verification_code' => $this->generateVerificationCode(),
            'expires_at' => Carbon::now()->addMinutes(self::VERIFICATION_EXPIRY_MINUTES),
        ]);

        // Store in memory for testing with reference
        $verification->id = count($this->mockVerifications) + 1;
        $this->mockVerifications[] = $verification;

        return $verification;
    }

    /**
     * Send verification code via SMS.
     */
    public function sendVerificationCode(string $phoneNumber): bool
    {
        if (! $this->canSendVerification($phoneNumber)) {
            return false;
        }

        $verification = $this->createVerification($phoneNumber);
        $message = $this->formatVerificationCode($verification->verification_code);

        return $this->sendSms($phoneNumber, $message);
    }

    /**
     * Verify a code for a phone number.
     */
    public function verifyCode(string $phoneNumber, string $code): bool
    {
        $verification = collect($this->mockVerifications)
            ->where('phone_number', $phoneNumber)
            ->filter(function ($v) {
                return ! $v->is_verified && $v->expires_at->gt(Carbon::now()) && $v->attempts < 5;
            })
            ->sortByDesc('created_at')
            ->first();

        if (! $verification) {
            return false;
        }

        $verification->attempts++;

        if ($verification->verification_code !== $code) {
            return false;
        }

        $verification->is_verified = true;
        $verification->verified_at = Carbon::now();

        return true;
    }

    // === RATE LIMITING ===

    /**
     * Check if verification can be sent to phone number.
     */
    public function canSendVerification(string $phoneNumber): bool
    {
        $recentCount = collect($this->mockVerifications)
            ->where('phone_number', $phoneNumber)
            ->where('created_at', '>=', Carbon::now()->subMinutes(self::RATE_LIMIT_WINDOW_MINUTES))
            ->count();

        return $recentCount < self::RATE_LIMIT_ATTEMPTS;
    }

    /**
     * Get time until next attempt is allowed.
     */
    public function getTimeUntilNextAttempt(string $phoneNumber): ?Carbon
    {
        if ($this->canSendVerification($phoneNumber)) {
            return null;
        }

        $oldestRecent = collect($this->mockVerifications)
            ->where('phone_number', $phoneNumber)
            ->where('created_at', '>=', Carbon::now()->subMinutes(self::RATE_LIMIT_WINDOW_MINUTES))
            ->sortBy('created_at')
            ->first();

        if (! $oldestRecent) {
            return null;
        }

        return $oldestRecent->created_at->addMinutes(self::RATE_LIMIT_WINDOW_MINUTES);
    }

    /**
     * Get initial delay for first attempt.
     */
    public function getInitialDelay(): int
    {
        return 0; // No delay for first attempt
    }

    /**
     * Get penalty delay after hitting rate limit.
     */
    public function getPenaltyDelay(string $phoneNumber): int
    {
        $violationCount = collect($this->mockVerifications)
            ->where('phone_number', $phoneNumber)
            ->where('created_at', '>=', Carbon::now()->subHour())
            ->count();

        // Progressive delay: 10 minutes + (violations * 5 minutes)
        return 10 + ($violationCount * 5);
    }

    // === ORDER NOTIFICATIONS ===

    /**
     * Send order confirmation SMS.
     */
    public function sendOrderConfirmation(string $phoneNumber, string $orderNumber, Carbon $readyTime): bool
    {
        $message = $this->formatOrderConfirmation($orderNumber, $readyTime);

        return $this->sendSmsWithRetry($phoneNumber, $message, 'order_confirmation');
    }

    /**
     * Send ready for pickup notification.
     */
    public function sendReadyNotification(string $phoneNumber, string $orderNumber, string $location): bool
    {
        $message = $this->formatReadyNotification($orderNumber, $location);

        return $this->sendSmsWithRetry($phoneNumber, $message, 'order_ready');
    }

    // === USER INTEGRATION ===

    /**
     * Send verification for user phone registration.
     */
    public function sendUserVerification(User $user, string $phoneNumber): SmsVerification
    {
        $this->validatePhoneNumber($phoneNumber);

        // Check if phone is already taken by another user
        $existingUser = User::where('phone', $phoneNumber)
            ->where('id', '!=', $user->id)
            ->whereNotNull('phone_verified_at')
            ->first();

        if ($existingUser) {
            throw new InvalidArgumentException('Phone number is already verified by another account');
        }

        $verification = $this->createVerification($phoneNumber, $user->id);
        $message = $this->formatVerificationCode($verification->verification_code);

        $this->sendSms($phoneNumber, $message);

        return $verification;
    }

    /**
     * Verify user phone and update user record.
     */
    public function verifyUserPhone(User $user, string $phoneNumber, string $code): bool
    {
        if (! $this->verifyCode($phoneNumber, $code)) {
            return false;
        }

        $user->update([
            'phone' => $phoneNumber,
            'phone_verified_at' => now(),
        ]);

        return true;
    }

    // === MESSAGE FORMATTING ===

    /**
     * Format verification code message.
     */
    public function formatVerificationCode(string $code): string
    {
        return "Your Havasu Lake Burritos verification code is {$code}. This code expires in 10 minutes.";
    }

    /**
     * Format order confirmation message.
     */
    public function formatOrderConfirmation(string $orderNumber, Carbon $readyTime): string
    {
        $timeStr = $readyTime->format('g:i A');

        return "Order {$orderNumber} confirmed! Your burritos will be ready around {$timeStr}. We'll text when ready.";
    }

    /**
     * Format ready notification message.
     */
    public function formatReadyNotification(string $orderNumber, string $location): string
    {
        return "Your order {$orderNumber} is ready for pickup at {$location}!";
    }

    /**
     * Format marketing message with opt-out.
     */
    public function formatMarketingMessage(string $content): string
    {
        return "{$content} Reply STOP to opt out of marketing messages.";
    }

    /**
     * Get all message templates.
     */
    public function getMessageTemplates(): array
    {
        return [
            'verification' => 'Your Havasu Lake Burritos verification code is {code}. This code expires in 10 minutes.',
            'order_confirmation' => 'Order {order_number} confirmed! Your burritos will be ready around {ready_time}.',
            'order_ready' => 'Your order {order_number} is ready for pickup at {location}!',
            'order_cancelled' => 'Order {order_number} has been cancelled. Any charges will be refunded.',
        ];
    }

    // === SMS SENDING ===

    /**
     * Send SMS with retry capability.
     */
    private function sendSmsWithRetry(string $phoneNumber, string $message, string $type): bool
    {
        $result = $this->sendSms($phoneNumber, $message);

        if (! $result) {
            $this->queuedRetries[] = [
                'phone_number' => $phoneNumber,
                'message' => $message,
                'type' => $type,
                'attempted_at' => now(),
            ];
        }

        return $result;
    }

    /**
     * Send SMS via Twilio (mocked for testing).
     */
    private function sendSms(string $phoneNumber, string $message): bool
    {
        $this->validatePhoneNumber($phoneNumber);

        if ($this->mockFailure) {
            $this->lastError = [
                'error_code' => 'MOCK_FAILURE',
                'error_message' => 'Mocked failure for testing',
                'timestamp' => now(),
                'phone_number' => $phoneNumber,
            ];

            $this->failedNotifications[] = [
                'phone_number' => $phoneNumber,
                'message' => $message,
                'error' => $this->lastError,
            ];

            return false;
        }

        // In production, this would use Twilio SDK
        Log::info('SMS sent', [
            'phone' => $phoneNumber,
            'message' => $message,
        ]);

        return true;
    }

    // === VALIDATION ===

    /**
     * Validate phone number format.
     */
    private function validatePhoneNumber(string $phoneNumber): void
    {
        // Basic E.164 format validation - allow test numbers
        if (! preg_match('/^\+1\d{10}$/', $phoneNumber)) {
            throw new InvalidArgumentException('Invalid phone number format. Must be E.164 format (+1XXXXXXXXXX)');
        }
    }

    // === TESTING HELPERS ===

    /**
     * Set mock failure state for testing.
     */
    public function setMockFailure(bool $fail): void
    {
        $this->mockFailure = $fail;
    }

    /**
     * Get failed notifications for testing.
     */
    public function getFailedNotifications(): array
    {
        return $this->failedNotifications;
    }

    /**
     * Get queued retries for testing.
     */
    public function getQueuedRetries(): array
    {
        return $this->queuedRetries;
    }

    /**
     * Get last error for testing.
     */
    public function getLastError(): ?array
    {
        return $this->lastError;
    }
}

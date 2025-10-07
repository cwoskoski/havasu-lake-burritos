<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'phone_number',
        'verification_code',
        'expires_at',
        'is_verified',
        'verified_at',
        'attempts',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'is_verified' => 'boolean',
        'attempts' => 'integer',
    ];

    protected $attributes = [
        'is_verified' => false,
        'attempts' => 0,
    ];

    // Add missing properties for in-memory usage
    public Carbon $created_at;

    public int $attempts = 0;

    public bool $is_verified = false;

    public ?Carbon $verified_at = null;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->created_at = Carbon::now();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if verification is still valid (not expired and not used).
     */
    public function isValid(): bool
    {
        return ! $this->is_verified &&
               $this->expires_at->isFuture() &&
               $this->attempts < 5;
    }

    /**
     * Check if verification has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Mark verification as used.
     */
    public function markAsVerified(): void
    {
        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }

    /**
     * Increment attempt counter.
     */
    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }

    // === QUERY SCOPES ===

    public function scopeForPhone(Builder $query, string $phoneNumber): Builder
    {
        return $query->where('phone_number', $phoneNumber);
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query->where('is_verified', false)
            ->where('expires_at', '>', now())
            ->where('attempts', '<', 5);
    }

    public function scopeRecent(Builder $query, int $minutes = 10): Builder
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }
}

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'phone_verified_at',
        'sms_notifications',
        'marketing_sms',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'sms_notifications' => 'boolean',
            'marketing_sms' => 'boolean',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Check if the user's phone number is verified.
     */
    public function isPhoneVerified(): bool
    {
        return ! is_null($this->phone_verified_at);
    }

    /**
     * Check if the user can place orders.
     * Requires verified phone number.
     */
    public function canPlaceOrders(): bool
    {
        return $this->hasCompleteProfile();
    }

    /**
     * Check if the user can receive SMS notifications.
     */
    public function canReceiveSms(): bool
    {
        return $this->isPhoneVerified() && $this->sms_notifications;
    }

    /**
     * Check if the user can receive marketing SMS.
     */
    public function canReceiveMarketingSms(): bool
    {
        return $this->canReceiveSms() && $this->marketing_sms;
    }

    /**
     * Check if the user has a complete profile for ordering.
     */
    public function hasCompleteProfile(): bool
    {
        return ! is_null($this->phone) && $this->isPhoneVerified();
    }

    /**
     * Get the user's phone number in a formatted display format.
     */
    public function getFormattedPhoneAttribute(): ?string
    {
        if (! $this->phone) {
            return null;
        }

        // Format +15551234567 as (555) 123-4567
        if (preg_match('/^\+1(\d{3})(\d{3})(\d{4})$/', $this->phone, $matches)) {
            return "({$matches[1]}) {$matches[2]}-{$matches[3]}";
        }

        return $this->phone;
    }

    /**
     * Verify the user's phone number.
     */
    public function verifyPhone(): void
    {
        $this->update(['phone_verified_at' => \Carbon\Carbon::now()]);
    }

    /**
     * Mark phone as unverified (for testing or security purposes).
     */
    public function unverifyPhone(): void
    {
        $this->update(['phone_verified_at' => null]);
    }

    /**
     * Update SMS notification preferences.
     */
    public function updateSmsPreferences(bool $notifications = true, bool $marketing = false): void
    {
        $this->update([
            'sms_notifications' => $notifications,
            'marketing_sms' => $marketing && $notifications, // Can't get marketing without notifications
        ]);
    }
}

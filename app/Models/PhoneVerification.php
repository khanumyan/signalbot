<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PhoneVerification extends Model
{
    protected $fillable = [
        'phone',
        'verification_code',
        'token',
        'telegram_chat_id',
        'verified',
        'expires_at',
    ];

    protected $casts = [
        'verified' => 'boolean',
        'expires_at' => 'datetime',
    ];

    /**
     * Generate a new verification record
     */
    public static function generate(string $phone): self
    {
        // Delete old unverified records for this phone
        self::where('phone', $phone)
            ->where('verified', false)
            ->delete();

        // Generate verification code (4 digits)
        $code = str_pad((string) rand(1000, 9999), 4, '0', STR_PAD_LEFT);

        // Generate unique token
        $token = Str::random(32);

        return self::create([
            'phone' => $phone,
            'verification_code' => $code,
            'token' => $token,
            'expires_at' => Carbon::now()->addMinutes(10), // 10 minutes expiration
        ]);
    }

    /**
     * Check if verification is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Verify the code (chat_id should already be set)
     */
    public function verify(): bool
    {
        if ($this->isExpired()) {
            return false;
        }

        if (!$this->telegram_chat_id) {
            return false;
        }

        $this->update([
            'verified' => true,
        ]);

        return true;
    }
}

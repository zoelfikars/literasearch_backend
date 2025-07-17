<?php

namespace App\Services;

use App\Mail\OtpMail;
use App\Models\OtpCode;
use App\Models\PasswordResetToken;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OtpService
{
    public static function generate(string $userId, string $email, string $nickname): void
    {
        $existing = OtpCode::where('user_id', $userId)
            ->where('expires_at', '>', now())
            ->first();
        if ($existing) {
            return;
        }
        $otp = random_int(100000, 999999);
        OtpCode::where('user_id', $userId)->delete();
        OtpCode::create([
            'user_id' => $userId,
            'otp' => $otp,
            'expires_at' => now()->addMinutes(5),
        ]);
        Mail::to($email, $nickname)->send(new OtpMail($otp));
    }
    public static function validate(string $userId, string $otp): bool
    {
        $record = OtpCode::where('user_id', $userId)
            ->where('otp', $otp)
            ->where('expires_at', '>', now())
            ->first();
        if ($record) {
            $record->delete();
            return true;
        }
        return false;
    }
    public static function createResetToken($userId): array
    {
        $token = Str::random(60);
        $expires_at = now()->addMinutes(15);
        PasswordResetToken::where('user_id', $userId)->delete();

        PasswordResetToken::create([
            'user_id' => $userId,
            'token' => hash('sha256', $token),
            'expires_at' => $expires_at,
        ]);

        return ['token' => $token, 'expires_at' => $expires_at];
    }
}

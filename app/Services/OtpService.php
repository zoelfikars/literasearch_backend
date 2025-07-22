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
    public static function generate(string $userId, string $email, string $nickname, string $purpose)
    {
        $existing = OtpCode::where('user_id', $userId)
            ->where('expires_at', '>', now())
            ->first();
        if ($existing) {
            return;
        }
        $otp = random_int(100000, 999999);
        OtpCode::where('user_id', $userId)->delete();
        $new_otp =  OtpCode::create([
            'user_id' => $userId,
            'otp' => $otp,
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes(5),
        ]);
        Mail::to($email, $nickname)->send(new OtpMail($otp));
        return $new_otp->expires_at;
    }
    public static function validate(string $userId, string $otp, string $purpose, string $condition)
    {
        if ($condition === 'validate') {
            $record = OtpCode::where('user_id', $userId)
                ->where('otp', $otp)
                ->where('purpose', $purpose)
                ->where('expires_at', '>', now())
                ->first();
        }
        if ($condition === 'update') {
            $record = OtpCode::where('user_id', $userId)
                ->where('otp', $otp)
                ->where('purpose', $purpose)
                ->whereNotNull('verified_at')
                ->first();
        }
        if ($record && $condition === 'validate') {
            $record->verified_at = now();
            $record->expires_at = now();
            $record->save();
            return true;
        }

        if ($record && $condition === 'update') {
            return true;
        }
        return false;
    }
}

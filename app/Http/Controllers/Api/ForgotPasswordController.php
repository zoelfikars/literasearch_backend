<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OtpRequest;
use App\Http\Requests\PasswordResetRequest;
use App\Http\Requests\ValidateOtpRequest;
use App\Http\Resources\OtpResource;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\OtpService;
use App\Traits\ApiResponse;

class ForgotPasswordController extends Controller
{
    use ApiResponse;
    public function forgotPasswordRequestOtp(OtpRequest $request)
    {
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return $this->setResponse('Tidak ada user yang terdaftar dengan email ' . $request->email . ' di sistem', null, 404);
        }

        $lastOtp = OtpCode::where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$lastOtp || $lastOtp->created_at->diffInSeconds(now()) >= 60) {
            $expires_at = OtpService::generate($user->id, $request->email, $user->nickname, 'password_reset');
        }

        return $this->setResponse(
            'Kode OTP berhasil dikirim',
            new OtpResource($user, $expires_at),
        );
    }
    public function forgotPasswordValidateOtp(ValidateOtpRequest $request)
    {
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return $this->setResponse('Tidak ada user yang terdaftar dengan email ' . $request->email . ' di sistem', null, 404);
        }

        if (!OtpService::validate($user->id, $request->otp, 'password_reset', 'validate')) {
            return $this->setResponse('Kode OTP tidak valid atau kadaluwarsa', null, 403);
        }

        return $this->setResponse('Kode OTP valid');
    }
    public function forgotPasswordReset(PasswordResetRequest $request)
    {
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return $this->setResponse('Tidak ada user yang terdaftar dengan email ' . $request->email . ' di sistem', null, 404);
        }

        if (!OtpService::validate($user->id, $request->otp, 'password_reset', 'update')) {
            return $this->setResponse('Kode OTP tidak valid atau kadaluwarsa', null, 403);
        }

        $user->password = bcrypt($request->password);
        $user->save();

        return $this->setResponse('Password berhasil direset');
    }
}

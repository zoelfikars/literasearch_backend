<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\OtpRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Http\Resources\LoginResource;
use App\Http\Resources\OtpResource;
use App\Http\Resources\ProfileResource;
use App\Models\OtpCode;
use App\Models\PasswordResetToken;
use App\Models\User;
use App\Services\OtpService;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponse;
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'nickname' => $request->nickname,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $ip = $request->ip();
        $platform = $request->platform;
        $expired = now()->addDays($request->remember ? 30 : 1);

        $tokenResult = $user->createToken('auth_token', ['*'], $expired);
        $token = $tokenResult->plainTextToken;
        $user->tokens()->where('id', $tokenResult->accessToken->id)->update(['ip_address' => $ip, 'platform' => $platform]);
        $user->syncRoles('user');
        $data = new LoginResource($token, $user, $expired);
        return $this->setResponse('Pendaftaran akun berhasil', $data, 201);
    }
    public function login(LoginRequest $request)
    {
        $user = User::with(['roles.permissions'])->where('email', $request->email)->first();
        if (!$user) {
            return $this->setResponse('Email belum terdaftar', null, 401);
        }
        if (!Hash::check($request->password, $user->password)) {
            return $this->setResponse('Email atau password tidak cocok', null, 401);
        }

        $ip = $request->ip();
        $platform = $request->platform;
        $expired = now()->addDays($request->remember ? 30 : 1);

        $existingToken = $user->tokens()
            ->where('platform', $platform)
            ->where('name', 'auth_token')
            ->where('ip_address', $ip)
            ->whereNull('revoked_at')
            ->first();

        if ($existingToken) {
            $revoked_at = now();
            $existingToken->revoked_at = $revoked_at;
            $existingToken->expires_at = $revoked_at;
            $existingToken->save();
        }

        $tokenResult = $user->createToken('auth_token', ['*'], $expired);
        $token = $tokenResult->plainTextToken;
        $user->tokens()->where('id', $tokenResult->accessToken->id)->update(['ip_address' => $ip, 'platform' => $platform]);
        $message = 'Login berhasil';

        $data = new LoginResource($token, $user, $expired);

        return $this->setResponse($message, $data, 200);
    }
    public function logout()
    {
        $token = Auth::user()->currentAccessToken();
        if (!$token) {
            return $this->setResponse('Token tidak ditemukan', null, 404);
        }

        $token->revoked_at = now();
        $token->expires_at = now();
        $token->save();

        return $this->setResponse('Berhasil logout', null, 200);
    }
    public function profile()
    {
        $user = Auth::user()->load(['roles', 'status']);
        if (!$user) {
            return $this->setResponse('Belum ada user yang login', null, 401);
        }
        $data = new ProfileResource($user);
        return $this->setResponse('Profil pengguna berhasil diambil', $data, 200);
    }
    public function requestOtp(OtpRequest $request)
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
            OtpService::generate($user->id, $request->email, $user->nickname);
        }

        return $this->setResponse(
            'Kode OTP berhasil dikirim',
            new OtpResource($user),
        );
    }
    public function verifyOtp(VerifyOtpRequest $request)
    {
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return $this->setResponse('Tidak ada user yang terdaftar dengan email ' . $request->email . ' di sistem', null, 404);
        }

        if (!OtpService::validate($user->id, $request->otp)) {
            return $this->setResponse('Kode OTP tidak valid atau kadaluwarsa', null, 403);
        }

        $resetToken = OtpService::createResetToken($user->id);

        return $this->setResponse('Kode OTP berhasil di verifikasi', $resetToken);
    }
    public function resetPassword(ResetPasswordRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        $record = PasswordResetToken::where('user_id', $user->id)
            ->where('token', hash('sha256', $request->reset_token))
            ->where('expires_at', '>', now())
            ->first();

        if (!$record) {
            return $this->setResponse('Token tidak valid atau sudah kadaluwarsa', null, 403);
        }

        $user->password = bcrypt($request->password);
        $user->save();

        $record->delete();

        $user->tokens()->update([
            'revoked_at' => now(),
            'expires_at' => now(),
        ]);

        return $this->setResponse('Password berhasil direset');
    }
}

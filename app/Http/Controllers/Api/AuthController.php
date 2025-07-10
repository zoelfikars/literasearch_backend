<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\Auth\LoginResource;
use App\Http\Resources\Auth\ProfileResource;
use App\Http\Resources\Auth\RegisterResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponse;
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);
        $data = new RegisterResource($user);
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

        $existingToken = $user->tokens()
            ->where('name', 'auth_token')
            ->where('ip_address', $ip)
            ->first();


        if ($existingToken) {
            if (Carbon::parse($existingToken->expires_at)->isPast()) {
                $existingToken->delete();
                $expired = now()->addDays($request->remember ? 30 : 1);
                $tokenResult = $user->createToken('auth_token', ['*'], $expired);
                $token = $tokenResult->plainTextToken;
                $message = 'Login berhasil dengan token baru';
            } else {
                $token = null;
                $expired = $existingToken->expires_at;
                $message = 'Anda sudah login';
            }
        } else {
            $expired = now()->addDays($request->remember ? 30 : 1);
            $tokenResult = $user->createToken('auth_token', ['*'], $expired);
            $token = $tokenResult->plainTextToken;

            $user->tokens()->where('id', $tokenResult->accessToken->id)->update(['ip_address' => $ip]);
            $message = 'Login berhasil';
        }
        $data = new LoginResource($token, $user, $expired);
        return $this->setResponse($message, $data, 200);
    }
    public function logout()
    {
        Auth::user()->currentAccessToken()->delete();
        return $this->setResponse('Logout berhasil', null, 200);
    }
    public function profile()
    {
        $user = Auth::user()->load(['roles.permissions', 'status']);
        if (!$user) {
            return $this->setResponse('Belum ada user yang login', null, 401);
        }
        $data = new ProfileResource($user);
        return $this->setResponse('Profil pengguna berhasil diambil', $data, 200);
    }
    // will be forgot password here
}

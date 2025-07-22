<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\EmailVerificationRequest;
use Illuminate\Foundation\Auth\EmailVerificationRequest as EmailVerificationRequestFoundation;

class EmailVerificationController extends Controller
{
    use ApiResponse;
    public function emailVerificationRequest(EmailVerificationRequest $request)
    {
        $user = Auth::user();
        if (!$user) {
            return $this->setResponse('Belum login', null, 401);
        }
        if ($user->hasVerifiedEmail()) {
            return $this->setResponse('Email sudah diverifikasi', null, 200);
        }
        $platform = $request->validated('platform');
        $user->sendCustomEmailVerificationNotification($platform);

        return $this->setResponse('Link verifikasi telah dikirim ke email.', null, 200);
    }
    public function emailVerificationVerify(EmailVerificationRequestFoundation $request)
    {
        if ($request->hasValidSignature()) {
            $request->fulfill();
            return $this->setResponse('Email berhasil diverifikasi.', null, 200);
        }
        return $this->setResponse('Invalid or expired verification link.', null, 400);
    }
}

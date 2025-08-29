<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Http\Requests\EmailVerificationRequest;
use App\Models\Status;
use Illuminate\Foundation\Auth\EmailVerificationRequest as EmailVerificationRequestFoundation;

class EmailVerificationController extends Controller
{
    use ApiResponse;
    public function emailVerificationRequest(EmailVerificationRequest $request)
    {
        $user = $request->user();
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
            $user = $request->user();
            $user->assignRole('Verified');
            $statusId = Status::where('type', 'user')->where('name', 'verified')->first()->value('id');
            $user->status_id = $statusId;
            $user->save();
            return $this->setResponse('Email berhasil diverifikasi.', null, 200);
        }
        return $this->setResponse('Invalid or expired verification link.', null, 400);
    }
}

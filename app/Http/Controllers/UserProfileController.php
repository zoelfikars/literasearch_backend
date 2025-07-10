<?php

namespace App\Http\Controllers;

use App\Http\Requests\IdentityProfileRequest;
use App\Http\Requests\SelfieProfileRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\UserProfile;
use App\Services\OcrKtpService;
use App\Services\UserProfileService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class UserProfileController extends Controller
{
    use ApiResponse;
    protected UserProfileService $userProfileService;

    public function __construct(UserProfileService $userProfileService)
    {
        $this->userProfileService = $userProfileService;
    }
    public function uploadIdentity(IdentityProfileRequest $request): JsonResponse
    {
        try {
            $profile = $this->userProfileService->uploadIdentity(
                auth()->user(),
                $request->file('identity_image'),
            );
            return $this->setResponse('Berhasil upload kartu identitas', $profile, 200);
        } catch (Throwable $th) {
            return $this->setErrorResponse('Gagal upload kartu identitas', 500, $th->getMessage());
        }
    }
    public function uploadSelfie(SelfieProfileRequest $request): JsonResponse
    {
        try {
            $profile = $this->userProfileService->uploadSelfie(
                auth()->user(),
                $request->file('selfie_image'),
            );
            return $this->setResponse('Berhasil upload', $profile, 200);
        } catch (Throwable $th) {
            return $this->setErrorResponse('Gagal upload', 500, $th->getMessage());
        }
    }
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $profile = $this->userProfileService->update(
                auth()->user(),
                $request->file('identity_image'),
                $request->file('selfie_image')
            );
            return $this->setResponse('Berhasil menyimpan profile', $profile, 200);
        } catch (Throwable $th) {
            return $this->setErrorResponse('Gagal menyimpan profile', 500, $th->getMessage());
        }
    }
}

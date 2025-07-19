<?php

namespace App\Services;

use App\Events\FileUploadedButDbFailed;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class UserProfileService
{
    protected OcrKtpService $ocrService;
    public function __construct(OcrKtpService $ocrService)
    {
        $this->ocrService = $ocrService;
    }
    public function uploadIdentity(User $user, UploadedFile $identityImage)
    {
        DB::beginTransaction();
        try {
            $userProfile = UserProfile::firstOrNew(['user_id' => $user->id]);
            $oldIdentityImagePath = $userProfile->identity_image_path;
            $newIdentityImagePath = $identityImage->store('identity_images', 'private');
            $userProfile->fill([
                'identity_image_path' => $newIdentityImagePath,
            ]);
            $userProfile->save();
            if ($oldIdentityImagePath && Storage::disk('private')->exists($oldIdentityImagePath)) {
                Storage::disk('private')->delete($oldIdentityImagePath);
            }
            DB::commit();
            return $userProfile;
        } catch (Throwable $e) {
            DB::rollBack();
            $filesToDeleteOnError = [];
            if (isset($newIdentityImagePath)) {
                $filesToDeleteOnError[] = $newIdentityImagePath;
            }
            if (!empty($filesToDeleteOnError)) {
                event(new FileUploadedButDbFailed($filesToDeleteOnError));
            }
            throw $e;
        }
    }
    public function uploadSelfie(User $user, UploadedFile $selfieImage)
    {
        DB::beginTransaction();
        try {
            $userProfile = UserProfile::firstOrNew(['user_id' => $user->id]);
            $oldSelfieImagePath = $userProfile->identity_image_path;
            $newSelfieImagePath = $selfieImage->store('selfie_images', 'private');
            $userProfile->fill([
                'selfie_image_path' => $newSelfieImagePath,
            ]);
            $userProfile->save();
            if ($oldSelfieImagePath && Storage::disk('private')->exists($oldSelfieImagePath)) {
                Storage::disk('private')->delete($oldSelfieImagePath);
            }
            DB::commit();
            return $userProfile;
        } catch (Throwable $e) {
            DB::rollBack();
            $filesToDeleteOnError = [];
            if (isset($newSelfieImagePath)) {
                $filesToDeleteOnError[] = $newSelfieImagePath;
            }
            if (!empty($filesToDeleteOnError)) {
                event(new FileUploadedButDbFailed($filesToDeleteOnError));
            }
            throw $e;
        }
    }
    public function identityOcr(UserProfile $user)
    {
        DB::beginTransaction();
        try {
            $userProfile = UserProfile::firstOrNew(['user_id' => $user->user_id]);

            $oldIdentityImagePath = $userProfile->identity_image_path;
            $oldSelfieImagePath = $userProfile->selfie_image_path;

            $newIdentityImagePath = $identityImage->store('identity_images', 'private');
            $newSelfieImagePath = $selfieImage->store('selfie_images', 'private');

            $ocrResult = $this->ocrService->extractData(storage_path("app/private/{$newIdentityImagePath}"));

            $userProfile->fill([
                'full_name' => $ocrResult['full_name'],
                'nik' => $ocrResult['nik'],
                'birth_place' => $ocrResult['birth_place'],
                'birth_date' => $ocrResult['birth_date'],
                'gender' => convert_gender_to_standard($ocrResult['gender']),
                'address' => $ocrResult['full_address'],
                'identity_image_path' => $newIdentityImagePath,
                'selfie_image_path' => $newSelfieImagePath,
            ]);
            $userProfile->save();
            if ($oldIdentityImagePath && Storage::disk('private')->exists($oldIdentityImagePath)) {
                Storage::disk('private')->delete($oldIdentityImagePath);
            }
            if ($oldSelfieImagePath && Storage::disk('private')->exists($oldSelfieImagePath)) {
                Storage::disk('private')->delete($oldSelfieImagePath);
            }
            DB::commit();
            return $userProfile;
        } catch (Throwable $e) {
            DB::rollBack();
            $filesToDeleteOnError = [];
            if (isset($newIdentityImagePath)) {
                $filesToDeleteOnError[] = $newIdentityImagePath;
            }
            if (isset($newSelfieImagePath)) {
                $filesToDeleteOnError[] = $newSelfieImagePath;
            }
            if (!empty($filesToDeleteOnError)) {
                event(new FileUploadedButDbFailed($filesToDeleteOnError));
            }
            throw $e;
        }
    }
    public function update() {
        
    }
}

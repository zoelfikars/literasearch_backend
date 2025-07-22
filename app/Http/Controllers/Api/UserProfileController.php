<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IdentityProfileRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Models\User;
use App\Services\UserProfileService;
use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Throwable;

class UserProfileController extends Controller
{
    use AuthorizesRequests;
    use ApiResponse;
    protected UserProfileService $userProfileService;

    public function __construct(UserProfileService $userProfileService)
    {
        $this->userProfileService = $userProfileService;
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
    public function update(UpdateProfileRequest $request)
    {
        try {
            $user = Auth::user()->load(['roles', 'status']);
            $user->nickname = $request->input('nickname', $user->nickname);
            if ($request->has('email') && $request->input('email') !== $user->email) {
                $user->email = $request->input('email');
                $user->email_verified_at = null;
            }
            if ($request->hasFile('profile_picture')) {
                $uploadedFile = $request->file('profile_picture');
                $filename = 'profile_' . $user->id . '.' . $uploadedFile->getClientOriginalExtension();

                $uploadedFile->storeAs('profile_pictures', $filename, 'private');

                $user->profile_picture_path = 'profile_pictures/' . $filename;
            }
            $user->save();
            $data = new ProfileResource($user);

            return $this->setResponse('Profil berhasil diperbarui', $data);
        } catch (Throwable $e) {
            dd($e->getMessage());
            return $this->setResponse('Terjadi kesalahan saat memperbarui profil', null, 500, 'error');
        }
    }
    public function serveProfilePicture(User $user)
    {
        $this->authorize('viewProfilePicture', $user);
        $path = $user->profile_picture_path;
        $imageExist = Storage::disk('private')->exists($path);
        if (!$imageExist) {
            abort(404);
        }
        return response()->file(
            Storage::disk('private')->path($user->profile_picture_path),
            ['Content-Type' => 'image/jpeg']
        );
    }
    public function uploadIdentity(IdentityProfileRequest $request)
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
}

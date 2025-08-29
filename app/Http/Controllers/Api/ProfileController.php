<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProfileController extends Controller
{
    use AuthorizesRequests;
    use ApiResponse;
    public function profile(Request $request)
    {
        $user = $request->user()->load(['roles', 'status', 'identity']);
        if (!$user) {
            return $this->setResponse('Belum ada user yang login', null, 401);
        }
        $data = new ProfileResource($user);
        return $this->setResponse('Profil pengguna berhasil diambil', $data, 200);
    }
    public function update(UpdateProfileRequest $request)
    {
        DB::beginTransaction();
        $picture_path = null;
        try {
            $user = $request->user()->load(['roles', 'status']);
            $user->nickname = $request->input('nickname', $user->nickname);
            if ($request->has('email') && $request->input('email') !== $user->email) {
                $loans = $user->loans();
                $loanCount = $loans->count();
                $canUpdate = $loanCount == 0;

                if (!$canUpdate) {
                    return $this->setErrorResponse('Anda tidak bisa mengubah email untuk sekarang ini', 500);
                }
                $user->email = $request->input('email');
                $user->email_verified_at = null;
            }
            if ($request->hasFile('profile_picture')) {
                $uploadedFile = $request->file('profile_picture');
                $filename = $user->id . '.' . $uploadedFile->getClientOriginalExtension();

                $uploadedFile->storeAs('profile_pictures', $filename, 'private');
                $picture_path = 'profile_pictures/' . $filename;
                $user->profile_picture_path = $picture_path;
            }
            $user->save();
            $data = new ProfileResource($user);

            DB::commit();
            return $this->setResponse('Profil berhasil diperbarui', $data);
        } catch (Throwable $e) {
            DB::rollBack();
            if ($uploadedFile) {
                Storage::disk('private')->delete('$picture_path');
            }
            return $this->setErrorResponse('Terjadi kesalahan saat memperbarui profil', 500);
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
}

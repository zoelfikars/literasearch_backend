<?php

namespace App\Http\Resources;

use App\Http\Resources\SimpleOptionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nickname' => $this->nickname,
            'email' => $this->email,
            'account_status' => new SimpleOptionResource($this->status),
            'email_verified_at' => $this->email_verified_at,
            'identity_complete' => $this->identity !== null ?? false,
            'profile_picture' => $this->profile_picture_path
                ? URL::signedRoute(
                    'api.user.profile.picture',
                    ['user' => $this],
                    now()->addMinutes(10)
                )
                : null,
            'roles' => $this->roles->pluck('name'),
        ];
    }
}

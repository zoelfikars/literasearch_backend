<?php

namespace App\Http\Resources;

use App\Http\Resources\SimpleOptionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'profile_picture' => $this->profile_picture_path,
            'roles' => $this->roles->pluck('name'),
        ];
    }
}

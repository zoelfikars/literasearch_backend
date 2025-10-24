<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MembershipInspectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'applied_at' => optional($this->created_at)->toIso8601String(),
            'status' => new StatusResource($this->status),
            'applicant' => [
                'nickname' => $this->user->nickname,
                'email' => $this->user->email,
                'identity' => new IdentityResource($this->user->identity),
            ],
        ];
        return $data;
    }
}

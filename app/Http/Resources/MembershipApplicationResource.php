<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MembershipApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'library_id' => $this->library_id,
            'user' => $this->relationLoaded('user') ? [
                'id' => $this->user->id,
                'nickname' => $this->user->nickname,
                'name' => $this->user->relationLoaded('identity') ? $this->user->identity->full_name : null,
                'email' => $this->user->email,
            ] : null,
            'status' => new StatusResource($this->status),
            'inspector' => $this->relationLoaded('inspector') ? [
                'id' => $this->inspector->id,
                'nickname' => $this->inspector->nickname,
                'inspected_at' => optional($this->inspector->inspected_at)->toIso8601String(),
            ] : null,
            'rejection_reason' => $this->rejection_reason,
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];

    }
}

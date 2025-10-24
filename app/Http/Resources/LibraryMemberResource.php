<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LibraryMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'library_id' => $this->library_id,
            'user' => [
                'id'        => $this->user?->id,
                'nickname'  => $this->user?->nickname,
                'name'      => $this->user?->identity?->full_name, // nama legal dari identity
                'email'     => $this->user?->email,
            ],
            'is_active' => (bool) $this->is_active,
            'joined_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}

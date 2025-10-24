<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LibraryApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'library' => $this->relationLoaded('library')
                ? new LibraryDetailResource($this->library)
                : null,
            'status' => $this->relationLoaded('status')
                ? new StatusResource($this->status)
                : null,
            'applicant' => $this->relationLoaded('user')
                ? (
                    $this->user && $this->user->identity
                    ? new IdentityResource($this->user->identity)
                    : null
                )
                : null,
            'document' => $this->document_path
                ? $this->document_signed_url
                : null,
            'inspector' => $this->relationLoaded('inspector')
                ? (
                    $this->inspector
                    ? [
                        'id' => $this->inspector->id,
                        'nickname' => $this->inspector->nickname,
                        'inspected_at' => optional($this->inspector->inspected_at)
                            ->toIso8601String(),
                    ]
                    : null
                )
                : null,
            'expiration_date' => optional($this->expiration_date)->toIso8601String(),
            'rejection_reason' => $this->rejection_reason,
        ];
        return $data;
    }
}

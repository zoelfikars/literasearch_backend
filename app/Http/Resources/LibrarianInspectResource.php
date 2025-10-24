<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LibrarianInspectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'applied_at' => optional($this->created_at)->toIso8601String(),
            // 'rejection_reason' => $this->rejection_reason,
            'status' => $this->relationLoaded('status') ? new StatusResource($this->status) : null,
            // 'inspector' => $this->relationLoaded('inspector')
            //     ? (
            //         $this->inspector
            //         ? [
            //             'id' => $this->inspector->id,
            //             'nickname' => $this->inspector->nickname,
            //             'inspected_at' => optional($this->inspected_at)->toIso8601String(),
            //             'rejected_at' => optional($this->rejected_at)->toIso8601String(),
            //         ]
            //         : null
            //     )
            //     : null,
            'applicant' => [
                // 'id' => $this->user->id,
                'nickname' => $this->user->nickname,
                'email' => $this->user->email,
                'identity' => $this->relationLoaded('user') ?
                    $this->user->relationLoaded('identity') ?
                    new IdentityResource($this->user->identity) :
                    null : null,
            ],
        ];
        return $data;

    }
}

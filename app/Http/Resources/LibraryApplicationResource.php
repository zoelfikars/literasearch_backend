<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

class LibraryApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'library' => $this->whenLoaded('library', function () {
                return new LibraryResource($this->library);
            }),
            'status' => $this->whenLoaded('status', function () {
                return $this->status->description;
            }),
            'applicant' => $this->whenLoaded('user', function () {
                if ($this->user->identity == null) {
                    return null;
                }
                return new IdentityResource($this->user->identity);
            }),
            'reviewer' => $this->whenLoaded('reviewer', function () {
                return [
                    'id' => $this->reviewer->id,
                    'name' => $this->reviewer->nickname,
                    'reviewed_at' => format_time($this->updated_at),
                ];
            }),
            'document' => $this->document_path
                ? URL::signedRoute(
                    'api.libraries.applications.document',
                    ['application' => $this],
                    now()->addMinutes(10)
                )
                : null,

        ];
        if ($this->expiration_date != null) {
            $data['expiration_date'] = format_date($this->expiration_date);
        }
        if ($this->rejected_reason != null) {
            $data['rejected_reason'] = $this->rejected_reason;
        }
        return $data;
    }
}

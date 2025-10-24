<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IdentityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'user_id' => $this->user_id,
            'full_name' => $this->full_name,
            'nik' => $this->nik,
            'birth_place' => $this->birth_place,
            'birth_date' => format_identity_date($this->birth_date),
            'gender' => convert_gender_to_indonesian($this->gender),
            'address' => $this->address,
            'phone' => $this->phone_number,
            'relationship' => $this->relationship ?? null,
            'identity_image_url' => $this->identity_image_path ? $this->signed_url : null,
        ];
        return $data;
    }
}

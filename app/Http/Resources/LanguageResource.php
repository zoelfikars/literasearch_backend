<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LanguageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->english_name,
            'iso_code' => $this->iso_639_1 ?? $this->iso_639_3,
            'direction' => $this->direction,
        ];
    }
}

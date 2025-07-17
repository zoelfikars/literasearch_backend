<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OtpResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'to' => maskEmail($this->email),
            'from' => config('mail.from.address'),
        ];
    }
}

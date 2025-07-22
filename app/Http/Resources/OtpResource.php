<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OtpResource extends JsonResource
{
    protected $expires_at;
    public function __construct($resource, $expires_at)
    {
        $this->expires_at = $expires_at;
        parent::__construct($resource);
    }
    public function toArray(Request $request): array
    {
        return [
            'to' => maskEmail($this->email),
            'from' => config('mail.from.address'),
            'expires_at' => $this->expires_at,
        ];
    }
}

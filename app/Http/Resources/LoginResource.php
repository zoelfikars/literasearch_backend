<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginResource extends JsonResource
{
    // public function __construct($token, $resource, $expired)
    public function __construct($token, $resource)
    {
        $this->token = $token;
        // $this->expired = $expired;
        parent::__construct($resource);
    }
    public function toArray(Request $request): array
    {
        return [
            ...($this->token === null ? [] : ['token' => $this->token]),
            'user' => [
                'id' => $this->id,
                'nickname' => $this->nickname,
                'permissions' => $this->roles->flatMap(fn($role) => $role->permissions->pluck('name'))->unique()->values(),
            ],
            // 'expires_at' => $this->expired,
        ];
    }
}

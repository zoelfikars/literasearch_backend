<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginResource extends JsonResource
{
    public function __construct($token, $resource, $expired)
    {
        $this->token = $token;
        $this->expired = $expired;
        parent::__construct($resource);
    }
    public function toArray(Request $request): array
    {
        return [
            ...($this->token === null ? [] : ['token' => $this->token]),
            'user' => [
                'id' => $this->id,
                'name' => $this->name,
                'email' => $this->email,
                'roles' => $this->roles->pluck('name'),
                'permissions' => $this->roles->flatMap(fn($role) => $role->permissions->pluck('name'))->unique()->values(),
            ],
            'expired_at' => $this->expired,
        ];
    }
}

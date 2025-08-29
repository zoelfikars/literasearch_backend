<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginResource extends JsonResource
{
    protected $token;
    public function __construct($token, $resource)
    {
        $this->token = $token;
        parent::__construct($resource);
    }
    public function toArray(Request $request): array
    {
        $user = [
            'id' => $this->id,
            'nickname' => $this->nickname,
            'permissions' => $this->roles->flatMap(fn($role) => $role->permissions->pluck('name'))->unique()->values(),
        ];

        $data = [];
        if ($this->token !== null) {
            $data['token'] = $this->token;
        }
        if($this->token !== null) {
            $data['user'] = $user;
        } else {
            $data = $user;
        }
        return $data;
    }
}

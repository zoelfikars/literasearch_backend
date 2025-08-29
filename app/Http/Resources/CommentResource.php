<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            'user_id' => $this->user->id,
            'nickname' => $this->user->nickname,
            'text' => $this->text,
            'created_at' => format_time($this->created_at),
        ];
    }
}

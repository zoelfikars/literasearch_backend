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
            'name' => $this->user->identity->full_name,
            'nickname' => $this->user->nickname,
            'text' => $this->text,
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}

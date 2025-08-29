<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SimpleOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $result = [
            'id' => $this->id,
            'name' => $this->name ?? $this->nickname,
        ];
        if (!empty($this->description)) {
            $result['description'] = $this->description;
        }
        return $result;
    }
}

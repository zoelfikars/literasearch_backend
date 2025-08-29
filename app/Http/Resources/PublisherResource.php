<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublisherResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $result = [
            'id' => $this->id,
            'name' => $this->name,
            'city' => $this->city,
        ];
        return $result;
    }
}

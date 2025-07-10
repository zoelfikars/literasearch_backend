<?php

namespace App\Http\Resources\libraries;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LibraryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'lon' => $this->lon,
            'lat' => $this->lat,
            'distance' => $this->distance,
        ];
    }
}

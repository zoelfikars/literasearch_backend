<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LibraryApplicationCollectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->library->name,
            'status' => $this->whenLoaded('status', fn() => $this->status->description),
        ];
        return $data;
    }
}

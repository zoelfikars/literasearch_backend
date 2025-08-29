<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookCollectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'title' => $this->title,
            'published_at' => $this->publication_date,
            'authors' => $this->authors,
            'isbn_10' => $this->isbn_10,
            'isbn_13' => $this->isbn_13,
            'rating' => 0,
        ];
        return $data;
    }
}

<?php

namespace App\Http\Resources\books;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookTrendingCollectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'cover_url' => $this->cover_url,
            'rating_count' => $this->rating_count,
        ];
    }
}

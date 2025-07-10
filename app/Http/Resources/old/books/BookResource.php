<?php

namespace App\Http\Resources\books;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'author' => $this->author,
            'cover_url' => $this->cover_url,
            'publication_year' => $this->publication_year,
            'subjects' => $this->subjects->pluck('name'),
        ];
    }
}

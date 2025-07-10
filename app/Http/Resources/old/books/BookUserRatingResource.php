<?php

namespace App\Http\Resources\books;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookUserRatingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'created_at' => $this->created_at->toDateTimeString(),
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'book' => [
                'id' => $this->book->id,
                'title' => $this->book->title,
            ],
        ];
    }
}

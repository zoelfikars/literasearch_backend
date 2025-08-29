<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'title' => $this->title->name,
            'subtitle' => $this->subtitle,
            'published_at' => $this->publication_date,
            'authors' => $this->whenLoaded('writers', fn() => $this->writers->pluck('name')->toArray()),
            'isbn_10' => $this->isbn_10,
            'isbn_13' => $this->isbn_13,
            'rating_avg' => (float) ($this->ratings_avg_rating ?? 0.0),
            'rating_count' => (int) ($this->ratings_count ?? 0),
        ];
        if (!empty($this->cover)) {
            $data['cover'] = $this->cover_signed_url;
        }
        return $data;
    }
}

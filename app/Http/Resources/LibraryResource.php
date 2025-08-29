<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LibraryResource extends JsonResource
{
    public function toArray(Request $request): array
    {

        $avg = (float) ($this->ratings_avg_rating ?? 0);
        $count = (int) ($this->ratings_count ?? 0);

        $user = $request->user('sanctum')?->id;
        $isLibrarian = $this->whenLoaded('librarians', function ($librarians) use ($user) {
            return $librarians->pluck('id')->contains($user);
        });

        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone_number,
            'address' => $this->address,
            'rating' => round($avg, 2),
            'rating_count' => $count,
            'physical_book_count' => 0,
            'distance' => format_distance($this->distance),
        ];
        if ($this->has_inspection) {
            $latestPending = $this->whenLoaded('latestPending');
            $inspectionId = $latestPending?->id;
            $data['inspection_id'] = $inspectionId;
        }
        $data['is_librarian'] = $isLibrarian;
        return $data;
    }
}

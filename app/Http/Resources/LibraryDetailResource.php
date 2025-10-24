<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LibraryDetailResource extends JsonResource
{
    public function toArray(Request $request)
    {
        $userId = $request->user('sanctum')?->id;
        $isLibrarian = $this->relationLoaded('librarians') && $userId
            ? $this->librarians->pluck('id')->contains($userId)
            : (bool) ($this->is_librarian_exists ?? false);
        $isMember = $this->relationLoaded('members') && $userId
            ? $this->members->pluck('id')->contains($userId)
            : (bool) ($this->is_member_exists ?? false);
        $avg = (float) ($this->ratings_avg_rating ?? 0);
        $count = (int) ($this->ratings_count ?? 0);
        $data = [
            "id" => $this->id,
            "name" => $this->name,
            "phone" => $this->phone_number,
            "description" => $this->description,
            "address" => $this->address,
            "latitude" => $this->latitude,
            "longitude" => $this->longitude,
            "is_recruiting" => (bool) $this->is_recruiting,
            "expiration_date" => ($this->relationLoaded('latestApprovedByExpiration') && $this->latestApprovedByExpiration != null && $this->latestApprovedByExpiration->expiration_date != null)
                ? optional($this->latestApprovedByExpiration->expiration_date)->toIso8601String()
                : null,
            "image" => $this->image_path ? $this->cover_signed_url : null,
            "is_librarian" => (bool) $isLibrarian,
            "is_member" => (bool) $isMember,
            'rating' => [
                'avg' => round($avg, 2),
                'count' => $count,
            ],
        ];
        return $data;
    }
}

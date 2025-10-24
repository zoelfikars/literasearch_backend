<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class EditionDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = auth('sanctum')->user();
        $hasRequiredRoles = $user && $user->hasRole('Completed Identity') && $user->hasRole('Verified');
        $resume = null;
        if (($this->user_locator_type ?? null) === 'cfi' && !empty($this->user_read_cfi)) {
            $resume = [
                'type' => 'cfi',
                'cfi' => $this->user_read_cfi,
                'percent' => (float) ($this->user_read_percent ?? 0),
                'at' => optional($this->user_last_opened_at)->toISOString() ?? null,
            ];
        }
        if (!$resume && ($this->user_locator_type ?? null) === 'page' && !empty($this->user_read_page)) {
            $pages = (int) ($this->pages ?? 0);
            $page = (int) $this->user_read_page;
            $percent = $pages > 0 ? min(100, round(($page / $pages) * 100, 2)) : 0;
            $resume = [
                'type' => 'page',
                'page' => $page,
                'percent' => (float) ($this->user_read_percent ?? $percent),
                'at' => optional($this->user_last_opened_at)->toISOString() ?? null,
            ];
        }
        $isLoggedIn = (bool) $request->user('sanctum');
        $hasLibraries = $this->relationLoaded('libraries') && $this->libraries;
        $activeLoansGlobal = (int) ($this->getAttribute('active_loans') ?? 0);
        $librariesBlock = null;
        if ($hasLibraries) {
            $aggregateTotal = $this->libraries->sum(function ($lib) {
                return (int) ($lib->pivot->stock_total ?? 0);
            });
            $available = max($aggregateTotal - $activeLoansGlobal, 0);
            $librariesBlock = $aggregateTotal > 0 ? [
                'total' => (int) $aggregateTotal,
                'available' => (int) $available,
            ] : null;
        }

        $data = [
            'id' => $this->id,
            'isbn' => $this->isbn_10,
            'isbn_13' => $this->isbn_13,
            'subtitle' => $this->subtitle,
            'description' => $this->description,
            'publication_year' => $this->publication_year,
            'edition_number' => $this->edition_number,
            'pages' => $this->pages,
            'cover' => $this->cover ? $this->cover_signed_url : null,
            'e_book' => $hasRequiredRoles ? [
                'url' => $this->file_path ? $this->ebook_url : null,
                'mime' => $this->file_path ? $this->ebook_mime : null,
                'size' => $this->file_path ? $this->ebook_size : null,
                'ext' => $this->file_path ? $this->ebook_ext : null,
            ] : null,

            'wishlisted' => isset($this->is_wishlisted) ? (bool) $this->is_wishlisted : null,
            'resume' => $resume,
            'stock' => $librariesBlock,
            'title' => $this->relationLoaded('title') ? new SimpleOptionResource($this->title) : null,
            'language' => $this->relationLoaded('language') ? new SimpleOptionResource($this->language) : null,
            'publisher' => $this->relationLoaded('publisher') ? [
                'id' => $this->publisher->id,
                'name' => $this->publisher->name,
                'city' => $this->publisher->city,
            ] : null,
            'authors' => $this->whenLoaded('contributors', function () {
                return $this->contributors
                    ->groupBy('id')
                    ->map(function ($items) {
                        $a = $items->first();
                        $roles = $items
                            ->map(fn($row) => [
                                'id' => $row->contributor_role_id,
                                'name' => $row->contributor_role_name,
                            ])
                            ->unique('id')
                            ->values()
                            ->toArray();
                        return [
                            'id' => $a->id,
                            'name' => $a->name,
                            'slug' => $a->slug,
                            'disambiguator' => $a->disambiguator,
                            'roles' => $roles,
                        ];
                    })
                    ->values()
                    ->toArray();
            }),
            'subjects' => $this->relationLoaded('subjects') ? $this->subjects->map(function ($subject) {
                return new SimpleOptionResource($subject);
            })->toArray() : [],
            'rating' => [
                'avg' => (float) ($this->ratings_avg_rating ?? 0.0),
                'count' => (int) ($this->ratings_count ?? 0),
            ],
        ];
        return $data;
    }
}

<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
class EditionResource extends JsonResource
{
    public const WRITER_ROLE_SLUG = 'penulis';
    public function toArray(Request $request): array
    {
        $requestLibraryId = optional($request->route('library'))->id ?? $request->get('library_id');
        $librariesBlock = null;
        $hasLibraries = $this->relationLoaded('libraries') && $this->libraries;
        $activeLoansGlobal = (int) ($this->getAttribute('active_loans') ?? 0);
        if ($hasLibraries) {
            if ($requestLibraryId) {
                $lib = $this->libraries->firstWhere('id', (int) $requestLibraryId) ?? $this->libraries->first();
                if ($lib) {
                    $stockTotal = (int) ($lib->pivot->stock_total ?? 0);
                    $computedFromQuery = $this->getAttribute('computed_stock_available');
                    $activeLoansInLib = $this->getAttribute('active_loans_in_library');
                    $available = !is_null($computedFromQuery)
                        ? (int) $computedFromQuery
                        : (!is_null($activeLoansInLib)
                            ? max($stockTotal - (int) $activeLoansInLib, 0)
                            : null);
                    $librariesBlock = [
                        'total' => $stockTotal,
                        'available' => $available,
                    ];
                }
            } else {
                $aggregateTotal = $this->libraries->sum(function ($lib) {
                    return (int) ($lib->pivot->stock_total ?? 0);
                });
                $available = max($aggregateTotal - $activeLoansGlobal, 0);
                $librariesBlock = $aggregateTotal > 0 ? [
                    'total' => (int) $aggregateTotal,
                    'available' => (int) $available,
                ] : null;
            }
        }
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
        $data = [
            'id' => $this->id,
            'isbn' => $this->isbn_13 ?? $this->isbn_10,
            'subtitle' => $this->subtitle,
            'publication_year' => $this->publication_year,
            'cover' => $this->cover ? $this->cover_signed_url : null,
            'resume' => $resume,
            'e_book' => $this->file_path ? [
                'url' => $this->ebook_url,
                'mime' => $this->ebook_mime,
                'size' => $this->ebook_size,
                'ext' => $this->ebook_ext,
            ] : null,
            'wishlisted' => isset($this->is_wishlisted) ? (bool) $this->is_wishlisted : null,
            'stock' => $librariesBlock,
            'title' => $this->relationLoaded('title') ? [
                'id' => $this->title->id,
                'name' => $this->title->name,
            ] : null,
            'authors' => $this->whenLoaded('writers', function () {
                return $this->writers
                    ->groupBy('id')
                    ->map(function ($items) {
                        $a = $items->first();
                        $roles = $items
                            ->map(fn($row) => [
                                'id' => $row->writer_role_id,
                                'name' => $row->writer_role_name,
                            ])
                            ->unique('id')
                            ->values()
                            ->toArray();
                        return [
                            'id' => $a->id,
                            'name' => $a->name,
                            'roles' => $roles,
                        ];
                    })
                    ->values()
                    ->toArray();
            }),
            'publisher' => $this->relationLoaded('publisher')
                ? [
                    'id' => $this->publisher->id,
                    'name' => $this->publisher->name
                ]
                : null,
            'rating' => [
                'avg' => (float) ($this->ratings_avg_rating ?? 0.0),
                'count' => (int) ($this->ratings_count ?? 0),
            ],
        ];
        return $data;
    }
}

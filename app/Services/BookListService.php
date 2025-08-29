<?php

namespace App\Services;

use App\Models\Edition;
use App\Models\Library;
use Illuminate\Http\Request;

class BookListService
{
    public function list(Request $request, ?Library $library = null)
    {
        $perPage = (int) $request->get('per_page', 15);
        $sort = $request->get('sort', 'created_at');
        $order = strtolower($request->get('order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $search = $request->get('search');

        $filters = [
            'title_id' => $request->get('title_id'),
            'publisher_id' => $request->get('publisher_id'),
            'language_id' => $request->get('language_id'),
            'author_id' => $request->get('author_id'),
            'role_id' => $request->get('role_id'),
            'subject_ids' => $request->get('subject_ids', []),
        ];
        $q = Edition::query()
            ->with([
                'title:id,title as name',
                'publisher:id,name',
                'language:id,english_name,native_name,iso_639_1,iso_639_3',
                'writers:id,name',
                'subjects:id,name',
                'ratingRecords',
            ])
            ->withRatingsAgg()
            ->when($search, fn($qq) => $qq->search($search))
            ->filter($filters);

        $allowed = [
            'created_at' => 'editions.created_at',
            'title' => 'book_titles.title',
            'rating' => 'ratings_avg_rating',
            'rating_count' => 'ratings_count',
        ];
        $col = $allowed[$sort] ?? 'editions.created_at';

        if ($col === 'book_titles.title') {
            $q->leftJoin('book_titles', 'book_titles.id', '=', 'editions.book_title_id')
                ->orderBy('book_titles.title', $order)
                ->select('editions.*');
        } elseif (($allowed[$sort] ?? null) === 'ratings_avg_rating') {
            $q->orderByRaw('COALESCE(ratings_avg_rating, 0) ' . $order);
        } elseif (($allowed[$sort] ?? null) === 'ratings_count') {
            $q->orderBy('ratings_count', $order);
        } else {
            $q->orderBy($allowed[$sort] ?? 'editions.created_at', $order);
        }

        return $q->paginate($perPage);
    }
}

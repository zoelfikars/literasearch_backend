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
        $order = $request->get('order', 'desc');
        $search = $request->get('search');

        $q = Edition::query()
            ->with([
                'title:id,name',
                'authors:id,name',
                'subjects:id,name',
            ])
            // ->when(
            //     $library,
            //     fn($qq) =>
            //     $qq->whereHas('stocks', fn($s) => $s->where('library_id', $library->id))
            // )
            ->when($search, fn($qq) => $qq->search($search));

        $allowed = [
            'created_at' => 'editions.created_at',
            'title' => 'book_titles.name',
        ];

        $col = $allowed[$sort] ?? 'editions.created_at';
        $dir = strtolower($order) === 'asc' ? 'asc' : 'desc';

        if ($col === 'book_titles.name') {
            $q->leftJoin('book_titles', 'book_titles.id', '=', 'editions.edition_title_id')
                ->orderBy('book_titles.name', $dir)
                ->select('editions.*');
        } else {
            $q->orderBy($col, $dir);
        }

        return $q->paginate($perPage);
    }
}

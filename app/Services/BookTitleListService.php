<?php

namespace App\Services;

use App\Models\BookTitle;

class BookTitleListService
{
    public function list(?string $search, int $perPage = 15)
    {
        return BookTitle::query()
            ->select(['id', 'title as name'])
            ->when($search, fn($q) => $q->search($search))
            ->orderBy('title')
            ->paginate($perPage);
    }
}

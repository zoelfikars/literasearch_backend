<?php

namespace App\Services;

use App\Models\Author;

class AuthorListService
{
    public function list(?string $search, int $perPage = 100)
    {
        return Author::query()
            ->when($search, fn($q) => $q->search($search))
            ->orderBy('name')
            ->paginate($perPage);
    }
}

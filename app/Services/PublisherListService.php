<?php

namespace App\Services;

use App\Models\Publisher;

class PublisherListService
{
    public function list(?string $search = null, int $perPage = 15)
    {
        return Publisher::query()
            ->select(['id', 'name', 'city'])
            ->when($search, fn($q) => $q->search($search))
            ->orderBy('name')
            ->paginate($perPage);
    }
}

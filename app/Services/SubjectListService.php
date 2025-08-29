<?php

namespace App\Services;

use App\Models\Subject;

class SubjectListService
{
    public function list(?string $search = null, int $perPage = 15)
    {
        return Subject::query()
            ->select(['id', 'name'])
            ->when($search, fn($q) => $q->search($search))
            ->orderBy('name')
            ->paginate($perPage);
    }
}

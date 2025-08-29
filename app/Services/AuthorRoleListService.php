<?php

namespace App\Services;

use App\Models\AuthorRole;

class AuthorRoleListService
{
    public function list(?string $search, int $perPage = 100)
    {
        return AuthorRole::query()
            ->when($search, fn($q) => $q->search($search))
            ->orderBy('name')
            ->paginate($perPage);
    }
}

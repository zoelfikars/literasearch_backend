<?php

namespace App\Services;

use App\Models\Language;

class LanguageListService
{
    public function list(?string $search, int $perPage = 100)
    {
        return Language::query()
            ->when($search, fn($q) => $q->search($search))
            ->orderBy('english_name')
            ->paginate($perPage);
    }
}

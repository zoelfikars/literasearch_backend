<?php

namespace App\Policies;

use App\Models\Library;
use App\Models\User;

class StockPolicy
{
    public function before(User $actor, $ability)
    {
        if ($actor->hasRole('Super Admin')) {
            return true;
        }
    }
    public function manage(User $actor, Library $library)
    {
        if ($actor->hasRole('Pustakawan')) {
            $managedLibraryIds = $actor->managedLibrariesActive()
                ->pluck('libraries.id');
            if ($managedLibraryIds->isNotEmpty()) {
                $isManaging = $managedLibraryIds->contains($library->id);
                if ($isManaging) {
                    return true;
                }
            }
        }
        return false;
    }
}

<?php

namespace App\Policies;

use App\Models\LibraryLibrarian;
use App\Models\User;

class LibraryLibrarianPolicy
{
    public function before(User $user, $ability)
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }
    }
    public function manage(User $actor, LibraryLibrarian $librarian)
    {
        if($librarian->library->owner_id === $librarian->user_id) {
            return false;
        }
        if ($actor->hasRole('Pustakawan')) {
            $managedLibraryIds = $actor->managedLibrariesActive()
                ->pluck('libraries.id');
            if ($managedLibraryIds->isNotEmpty()) {
                $isManaging = $managedLibraryIds->contains($librarian->library_id);
                if ($isManaging) {
                    return true;
                }
            }
        }
        return false;
    }
}

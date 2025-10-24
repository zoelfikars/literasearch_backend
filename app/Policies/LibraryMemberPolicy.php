<?php

namespace App\Policies;

use App\Models\LibraryMember;
use App\Models\User;

class LibraryMemberPolicy
{
    public function before(User $user, $ability)
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }
    }
    public function manage(User $actor, LibraryMember $member)
    {
        if ($actor->hasRole('Pustakawan')) {
            $managedLibraryIds = $actor->managedLibrariesActive()
                ->pluck('libraries.id');
            if ($managedLibraryIds->isNotEmpty()) {
                $isManaging = $managedLibraryIds->contains($member->library_id);
                if ($isManaging) {
                    return true;
                }
            }
        }
        return false;
    }
}

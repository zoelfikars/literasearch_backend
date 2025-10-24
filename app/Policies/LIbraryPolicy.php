<?php

namespace App\Policies;

use App\Models\Library;
use App\Models\LibraryLibrarian;
use App\Models\User;

class LibraryPolicy
{
    public function edit(User $user, Library $library)
    {
        $is_librarian = LibraryLibrarian::where('library_id', $library->id)->where('user_id', $user->id)->first();

        return $is_librarian && $is_librarian->is_active;
    }
    public function apply(User $user, Library $library)
    {
        return $user->hasRole('Verified') && $user->hasRole('Completed Identity');
    }
}

<?php

namespace App\Policies;

use App\Models\LibraryApplication;
use App\Models\User;

class LibraryApplicationPolicy
{
    public function viewLibraryApplicationDocument(User $authUser, LibraryApplication $targetUser)
    {
        return $authUser->id === $targetUser->user_id || $authUser->hasRole('Pustakawan Nasional');
    }
}

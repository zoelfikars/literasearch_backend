<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewProfilePicture(User $authUser, User $targetUser)
    {
        return $authUser->id === $targetUser->id;
    }
}

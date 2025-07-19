<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewProfileData(User $authUser, User $targetUser)
    {
        return $authUser->id === $targetUser->id;
    }
    public function viewGuardianData(User $authUser, User $targetUser)
    {
        return $authUser->id === $targetUser->id;
    }
    public function viewProfilePicture(User $authUser, User $targetUser)
    {
        return $authUser->id === $targetUser->id;
    }
}

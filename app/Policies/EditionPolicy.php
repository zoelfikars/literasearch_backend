<?php
namespace App\Policies;
use App\Models\User;
class EditionPolicy
{
    public function before(User $user, $ability)
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }
    }
    public function manage(User $actor)
    {
        return $actor->hasRole('Pustakawan');
    }
    public function read(User $actor)
    {
        return $actor->hasRole('Verified');
    }
}

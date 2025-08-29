<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BookPolicy
{
    use HandlesAuthorization;
    public function before(User $user, $ability)
    {
        if ($user->hasRole('Super Admin') || $user->hasRole('Pustakawan Nasional')) {
            return true;
        }
    }
    public function store(User $actor)
    {
        return $actor->hasRole('Pustakawan');
    }
    public function update(User $actor)
    {
        return $actor->hasRole('Pustakawan');
    }
    public function delete(User $actor)
    {
        return $actor->hasRole('Pustakawan');
    }
}

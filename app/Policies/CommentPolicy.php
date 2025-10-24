<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CommentPolicy
{
    public function before(User $user, $ability)
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }
    }
    public function delete(User $actor, Model $comment)
    {
        return $actor->hasRole('Verified') && $actor->id === $comment->user_id;
    }
    public function store(User $actor)
    {
        return $actor->hasRole('Verified');
    }

}

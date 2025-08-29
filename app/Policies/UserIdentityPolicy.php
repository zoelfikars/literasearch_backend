<?php

namespace App\Policies;

use App\Models\LibrarianApplication;
use App\Models\LibraryApplication;
use App\Models\MembershipApplication;
use App\Models\User;
use App\Models\UserIdentity;
use App\Traits\ApiResponse;

class UserIdentityPolicy
{
    use ApiResponse;
    public function updateIdentityData(User $loggedInUser, UserIdentity $userIdentity)
    {
        return $loggedInUser->id === $userIdentity->user_id;
    }
    public function viewIdentityData(User $loggedInUser, UserIdentity $userIdentity)
    {
        return $loggedInUser->id === $userIdentity->user_id || $loggedInUser->hasRole('Pustakawan Nasional') || $loggedInUser->hasRole('Super Admin');
    }
    public function viewIdentityImage(User $actor, UserIdentity $identity)
    {
        if ($actor->id === $identity->user_id) {
            return true;
        }
        if ($actor->hasRole('Super Admin')) {
            return true;
        }
        $owner = $identity->user()->with('status')->first();
        $isBlacklisted = $owner?->status
            && $owner->status->type === 'user'
            && $owner->status->name === 'blacklisted';
        if ($isBlacklisted && ($actor->hasRole('Pustakawan') || $actor->hasRole('Pustakawan Nasional'))) {
            return true;
        }

        if ($actor->hasRole('Pustakawan')) {
            $managedLibraryIds = $actor->managedLibraries()
                ->wherePivot('is_active', true)
                ->pluck('id');
            if ($managedLibraryIds->isNotEmpty()) {
                $exists = LibrarianApplication::query()
                    ->where('user_id', $identity->user_id)
                    ->whereIn('library_id', $managedLibraryIds)
                    ->whereHas('status', function ($q) {
                        $q->where('type', 'librarian_application')
                            ->where('name', 'pending');
                    })
                    ->exists();
                if ($exists) {
                    return true;
                }
            }
        }
        if ($actor->hasRole('Pustakawan Nasional')) {
            $exists = LibraryApplication::query()
                ->where('user_id', $identity->user_id)
                ->whereHas('status', function ($q) {
                    $q->where('type', 'library_application')
                        ->where('name', 'pending');
                })
                ->exists();
            if ($exists) {
                return true;
            }
        }
        return false;
    }
}

<?php

namespace App\Policies;

use App\Models\Library;
use App\Models\LibraryApplication;
use App\Models\User;
use Illuminate\Support\Carbon;

class LibraryApplicationPolicy
{
    public function before(User $user, $ability)
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }
    }
    public function viewDocument(User $actor, LibraryApplication $application)
    {
        if ($actor->id === $application->user_id) {
            return true;
        }
        if ($actor->hasRole('Pustakawan Nasional')) {
            $is_pending = $application->pending();
            if ($is_pending) {
                return true;
            }
        }
        $managedLibraryIds = $actor->managedLibrariesActive()
            ->pluck('libraries.id');
        if ($managedLibraryIds->isNotEmpty() && $managedLibraryIds->contains($application->library_id) && $actor->hasRole('Pustakawan')) {
            return true;
        }
        return false;
    }
    public function approve(User $actor, LibraryApplication $application)
    {
        if ($actor->hasRole('Pustakawan Nasional')) {
            $is_pending = $application->pending();
            if ($is_pending) {
                return true;
            }
        }
        return false;
    }
    public function reject(User $actor, LibraryApplication $application)
    {
        if ($actor->hasRole('Pustakawan Nasional')) {
            $is_pending = $application->pending();
            if ($is_pending) {
                return true;
            }
        }
        return false;
    }
    public function extend(User $actor, Library $library)
    {
        if ($actor->hasRole('Pustakawan')) {
            $managedLibraryIds = $actor->managedLibrariesActive()
                ->pluck('libraries.id');
            if ($managedLibraryIds->isNotEmpty() && $managedLibraryIds->contains($library->id)) {

                return true;
            }
        }
        return false;
    }
}

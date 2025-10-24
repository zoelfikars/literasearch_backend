<?php

namespace App\Policies;

use App\Models\LibrarianApplication;
use App\Models\User;

class LibrarianApplicationPolicy
{
    public function before(User $user, $ability)
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }
    }
    
    public function view(User $actor, $library)
    {
        if ($actor->hasRole('Pustakawan')) {
            $managedLibraryIds = $actor->managedLibrariesActive()
                ->pluck('libraries.id');
            if ($managedLibraryIds->isNotEmpty()) {
                $isManaging = $managedLibraryIds->contains($library);
                if ($isManaging) {
                    return true;
                }
            }
        }
        return false;
    }
    public function approve(User $actor, LibrarianApplication $application)
    {
        if ($actor->hasRole('Pustakawan')) {
            $is_pending = $application->pending();
            if ($is_pending) {
                $managedLibraryIds = $actor->managedLibrariesActive()
                    ->pluck('libraries.id');
                if ($managedLibraryIds->isNotEmpty()) {
                    $isManaging = $managedLibraryIds->contains($application->library_id);
                    if ($isManaging) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
    public function reject(User $actor, LibrarianApplication $application)
    {
        if ($actor->hasRole('Pustakawan')) {
            $is_pending = $application->pending();
            if ($is_pending) {
                if ($is_pending) {
                    $managedLibraryIds = $actor->managedLibrariesActive()
                        ->pluck('libraries.id');
                    if ($managedLibraryIds->isNotEmpty()) {
                        $isManaging = $managedLibraryIds->contains($application->library_id);
                        if ($isManaging) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }
}

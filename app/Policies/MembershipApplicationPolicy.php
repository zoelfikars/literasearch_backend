<?php
namespace App\Policies;
use App\Models\Library;
use App\Models\MembershipApplication;
use App\Models\User;
class MembershipApplicationPolicy
{
    public function before(User $user, $ability)
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }
    }
    public function view(User $actor, $library_id)
    {
        if ($actor->hasRole('Pustakawan')) {
            $managedLibraryIds = $actor->managedLibrariesActive()
                ->pluck('libraries.id');
            if ($managedLibraryIds->isNotEmpty()) {
                $isManaging = $managedLibraryIds->contains($library_id);
                if ($isManaging) {
                    return true;
                }
            }
        }
        return false;
    }

    public function approve(User $actor, MembershipApplication $application)
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
    public function reject(User $actor, MembershipApplication $application)
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

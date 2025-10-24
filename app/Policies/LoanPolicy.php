<?php

namespace App\Policies;

use App\Models\Edition;
use App\Models\Library;
use App\Models\LibraryMember;
use App\Models\Loan;
use App\Models\User;

class LoanPolicy
{
    public function rent(User $actor, Library $library)
    {
        if (!$library->is_active) {
            return false;
        }
        $blacklisted = $actor->hasRole('Blacklist');
        $memberInLibraries = LibraryMember::where('user_id', $actor->id)
            ->where('library_id', $library->id)
            ->whereNull('deleted_at')
            ->exists();

        if (!$memberInLibraries || $blacklisted) {
            return false;
        }

        if (!$actor->hasRole('Member') || !$actor->hasRole('Verified') || !$actor->hasRole('Completed Identity')) {
            return false;
        }
        return true;
    }
    public function view(User $actor, Library $library)
    {
        if ($actor->hasRole('Pustakawan')) {
            $managedLibraryIds = $actor->managedLibrariesActive()
                ->pluck('libraries.id');
            if ($managedLibraryIds->isNotEmpty()) {
                $isManaging = $managedLibraryIds->contains($library->id);
                if ($isManaging) {
                    return true;
                }
            }
        }
        return false;
    }
    public function action(User $actor, Loan $loan)
    {
        if ($actor->hasRole('Pustakawan')) {
            $managedLibraryIds = $actor->managedLibrariesActive()
                ->pluck('libraries.id');
            if ($managedLibraryIds->isNotEmpty()) {
                $isManaging = $managedLibraryIds->contains($loan->library_id);
                if ($isManaging) {
                    return true;
                }
            }
        }
        return false;
    }
    public function return(User $actor, Loan $loan)
    {
        if ($actor->hasRole('Pustakawan')) {
            $managedLibraryIds = $actor->managedLibrariesActive()
                ->pluck('libraries.id');
            if ($managedLibraryIds->isNotEmpty()) {
                $isManaging = $managedLibraryIds->contains($loan->library_id);
                if ($isManaging) {
                    return true;
                }
            }
        }
        return false;
    }
}

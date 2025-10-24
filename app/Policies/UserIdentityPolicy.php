<?php
namespace App\Policies;
use App\Models\LibrarianApplication;
use App\Models\LibraryApplication;
use App\Models\LibraryMember;
use App\Models\Loan;
use App\Models\MembershipApplication;
use App\Models\User;
use App\Models\UserIdentity;
class UserIdentityPolicy
{
    public function before(User $user, $ability)
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }
    }
    public function update(User $actor, UserIdentity $identity)
    {
        $hasBlacklistRole = $actor->hasRole('Blacklist');
        $overdueStatusLoans = $actor->loans()
            ->whereNull('returned_at')
            ->loanStatus('overdue')
            ->exists();
        $overdueApprovedLoans = $actor->loans()
            ->whereNull('returned_at')
            ->loanStatus('approved')
            ->exists();
        if ($hasBlacklistRole || $overdueStatusLoans || $overdueApprovedLoans) {
            return false;
        }
        return $actor->id === $identity->user_id;
    }
    public function view(User $actor, UserIdentity $identity)
    {
        if ($actor->id === $identity->user_id) {
            return true;
        }
        if ($actor->hasRole('Pustakawan')) {
            $managedLibraryIds = $actor->managedLibrariesActive()
                ->pluck('libraries.id');
            if ($managedLibraryIds->isNotEmpty()) {
                $existsMembershipPending = MembershipApplication::query()
                    ->where('user_id', $identity->user_id)
                    ->whereIn('library_id', $managedLibraryIds)
                    ->whereHas('status', function ($q) {
                        $q->where('type', 'membership_application')
                            ->where('name', 'pending');
                    })
                    ->exists();
                if ($existsMembershipPending) {
                    return true;
                }
                $blacklisted = $identity->user->hasRole('Blacklist');
                if ($blacklisted) {
                    return true;
                }
                $existsLibraryAppPending = LibrarianApplication::query()
                    ->where('user_id', $identity->user_id)
                    ->whereIn('library_id', $managedLibraryIds)
                    ->whereHas('status', function ($q) {
                        $q->where('type', 'librarian_application')
                            ->where('name', 'pending');
                    })
                    ->exists();
                if ($existsLibraryAppPending) {
                    return true;
                }
                $existLoanPending = Loan::query()
                    ->where('user_id', $identity->user_id)
                    ->whereIn('library_id', $managedLibraryIds)
                    ->whereHas('status', function ($q) {
                        $q->where('type', 'loan')
                            ->where('name', 'pending');
                    })
                    ->exists();
                if ($existLoanPending) {
                    return true;
                }

            }
        }
        if ($actor->hasRole('Pustakawan Nasional')) {
            $existsLibraryAppPending = LibraryApplication::query()
                ->where('user_id', $identity->user_id)
                ->whereHas('status', function ($q) {
                    $q->where('type', 'library_application')
                        ->where('name', 'pending');
                })
                ->exists();
            if ($existsLibraryAppPending) {
                return true;
            }
        }
        return false;
    }
}

<?php
namespace App\Services;
use App\Http\Requests\HistoryListRequest;
use App\Models\LibrarianApplication;
use App\Models\LibraryApplication;
use App\Models\Loan;
use App\Models\MembershipApplication;
use App\Models\User;
class HistoryService
{
    public function list(HistoryListRequest $request, User $user)
    {
        $section = $request->get('section');
        $search = trim((string) $request->get('search', ''));
        $perPage = (int) $request->get('per_page', 15);
        $order = strtolower($request->get('order', 'desc')) === 'asc' ? 'asc' : 'desc';
        return match ($section) {
            'library_applications' => $this->libraryApplications($user, $search, $order, $perPage),
            'librarian_applications' => $this->librarianApplications($user, $search, $order, $perPage),
            'membership_applications' => $this->membershipApplications($user, $search, $order, $perPage),
            'loans' => $this->loans($user, $search, $order, $perPage),
        };
    }
    private function libraryApplications(User $user, ?string $q, string $order, int $perPage)
    {
        return LibraryApplication::query()
            ->ownedBy($user->id)
            ->with([
                'library:id,name',
                'status:id,name,type,description',
                'inspector:id,nickname',
            ])
            ->search($q)
            ->orderBy('created_at', $order)
            ->paginate($perPage);
    }
    private function librarianApplications(User $user, ?string $q, string $order, int $perPage)
    {
        return LibrarianApplication::query()
            ->ownedBy($user->id)
            ->with([
                'library:id,name',
                'status:id,name,type,description',
                'inspector:id,nickname',
            ])
            ->search($q)
            ->orderBy('created_at', $order)
            ->paginate($perPage);
    }
    private function membershipApplications(User $user, ?string $q, string $order, int $perPage)
    {
        return MembershipApplication::query()
            ->ownedBy($user->id)
            ->with([
                'library:id,name',
                'user:id,nickname',
                'status:id,name,type,description',
                'inspector:id,nickname',
            ])
            ->search($q)
            ->orderBy('created_at', $order)
            ->paginate($perPage);
    }
    private function loans(User $user, ?string $q, string $order, int $perPage)
    {
        return Loan::query()
            ->ownedBy($user->id)
            ->with([
                'library:id,name',
                'edition:id,book_title_id',
                'edition.title:id,title',
                'status:id,name,type,description',
                'inspector:id,nickname',
            ])
            ->search($q)
            ->orderBy('created_at', $order)
            ->paginate($perPage);
    }
}

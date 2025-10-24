<?php

namespace App\Services;

use App\Http\Requests\LibrarianFilterRequest;
use App\Models\LibrarianApplication;
use App\Models\LibraryLibrarian;
use App\Models\User;

class LibrarianListService
{
    public function list(LibrarianFilterRequest $request, ?User $user, string $libraryId)
    {
        $perPage = (int) $request->get('per_page', 15);
        $search = trim((string) $request->get('search', ''));
        $sort = $request->get('sort', 'created_at');
        $order = $request->get('order', 'desc');
        return LibraryLibrarian::query()
            ->forLibrary($libraryId)
            ->with([
                'library.firstApproved.status',
                'library.firstApproved.inspector',
                'user.identity',
                'approvedApplication.status',
                'approvedApplication.inspector',
            ])
            ->searchUser($search)
            ->orderByAllowed($sort, $order)
            ->paginate($perPage);
    }
    public function listApplications(LibrarianFilterRequest $request, ?User $user, string $libraryId)
    {
        $perPage = (int) $request->get('per_page', 15);
        $search = trim((string) $request->get('search', ''));
        $sort = $request->get('sort', 'created_at');
        $order = $request->get('order', 'desc');
        return LibrarianApplication::query()
            ->forLibrary($libraryId)
            ->with([
                'user.identity',
                'status',
                'inspector.identity',
            ])
            ->pending()
            ->searchUser($search)
            ->orderByAllowed($sort, $order)
            ->paginate($perPage);
    }
}

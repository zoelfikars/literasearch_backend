<?php
namespace App\Services;
use App\Http\Requests\MembershipFilterRequest;
use App\Models\LibraryMember;
use App\Models\MembershipApplication;
use App\Models\User;
class MembershipListService
{
    const VALID_SORT_COLUMNS = [
        'created_at',
        'name',
    ];
    const VALID_STATUS_FILTERS = [
        'pending',
        'is_active',
        'inactive',
        'pending'
    ];
    public function listMembers(MembershipFilterRequest $request, ?User $user, string $libraryId)
    {
        $perPage = (int) $request->get('per_page', 15);
        $search = trim((string) $request->get('search', ''));
        $sort = $request->get('sort', 'created_at');
        $order = $request->get('order', 'desc');
        $sortColumn = 'created_at';
        $statusFilter = null;
        if (in_array($sort, self::VALID_SORT_COLUMNS)) {
            $sortColumn = $sort;
        } elseif (in_array($sort, self::VALID_STATUS_FILTERS)) {
            $statusFilter = $sort;
        }
        return LibraryMember::query()
            ->forLibrary($libraryId)
            ->with([
                'user.identity',
                'approvedApplication.status',
                'approvedApplication.inspector',
            ])
            ->search($search)
            ->filterByStatus($statusFilter)
            ->orderByAllowed($sortColumn, $order)
            ->paginate($perPage);
    }
    public function listApplications(MembershipFilterRequest $request, ?User $user, string $libraryId)
    {
        $perPage = (int) $request->get('per_page', 15);
        $search = trim((string) $request->get('search', ''));
        $sort = $request->get('sort', 'created_at');
        $order = $request->get('order', 'desc');
        $query = MembershipApplication::query()
            ->forLibrary($libraryId)
            ->with([
                'user.identity',
                'status',
                'inspector.identity',
            ])
            ->pending()
            ->search($search)
            ->orderByAllowed($sort, $order)
            ->paginate($perPage);
        return $query;
    }
}

<?php
namespace App\Services;
use App\Models\Library;
use App\Models\Loan;
use Illuminate\Http\Request;
class LoanListService
{
    const VALID_SORT_COLUMNS = [
        'created_at',
        'book_name',
        'borrower_name',
        'loaned_at',
        'due_date',
        'returned_at'
    ];
    const VALID_STATUS_FILTERS = [
        'pending',
        'not_returned',
        'returned',
        'overdue'
    ];
    public function list(Request $request, ?Library $library = null)
    {
        $perPage = (int) $request->get('per_page', 15);
        $sort = $request->get('sort', 'created_at');
        $order = strtolower($request->get('order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $search = $request->get('search');
        $libraryId = $library?->id ?? $request->get('library_id') ?? null;
        $sortColumn = 'created_at';
        $statusFilter = null;
        if (in_array($sort, self::VALID_SORT_COLUMNS)) {
            $sortColumn = $sort;
        } elseif (in_array($sort, self::VALID_STATUS_FILTERS)) {
            $statusFilter = $sort;
        }
        $q = Loan::query()
            ->with([
                'library:id,name',
                'edition:id,subtitle,isbn_10,isbn_13,book_title_id',
                'edition.title:id,title',
                'borrower:id,nickname,email',
                'borrower.identity:full_name,user_id',
                'inspector:id,nickname',
                'status:id,type,name,description',
            ])
            ->when($libraryId, fn($qq, $id) => $qq->inLibrary($id))
            ->search($search)
            ->filterByStatus($statusFilter)
            ->orderByAllowed($sortColumn, $order);
        return $q->paginate($perPage);
    }
}

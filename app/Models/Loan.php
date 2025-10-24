<?php
namespace App\Models;
use DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Loan extends Model
{
    use HasFactory, HasUuids, SoftDeletes;
    protected $fillable = [
        'user_id',
        'edition_id',
        'library_id',
        'status_id',
        'loaned_at',
        'due_date',
        'returned_at',
        'inspected_at',
        'notes',
        'inspector_id',
        'rejection_reason'
    ];
    protected $casts = [
        'loaned_at' => 'datetime',
        'due_date' => 'datetime',
        'returned_at' => 'datetime',
        'inspected_at' => 'datetime',
    ];
    protected $keyType = 'string';
    public $incrementing = false;
    public function library()
    {
        return $this->belongsTo(Library::class)->withTrashed();
    }
    public function edition()
    {
        return $this->belongsTo(Edition::class);
    }
    public function borrower()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }
    public function status()
    {
        return $this->belongsTo(Status::class);
    }
    public function scopeOwnedBy(Builder $q, string $userId): Builder
    {
        return $q->where('loans.user_id', $userId);
    }
    public function scopeInLibrary(Builder $q, string $libraryId): Builder
    {
        return $q->where('library_id', $libraryId);
    }
    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (!$term)
            return $q;
        $like = '%' . mb_strtolower($term) . '%';
        return $q
            ->leftJoin('users as b_users', 'b_users.id', '=', 'loans.user_id')
            ->leftJoin('editions as b_editions', 'b_editions.id', '=', 'loans.edition_id')
            ->leftJoin('book_titles as b_titles', 'b_titles.id', '=', 'b_editions.book_title_id')
            ->leftJoin('statuses as b_statuses', 'b_statuses.id', '=', 'loans.status_id')
            ->where(function ($w) use ($like) {
                $w->whereRaw('LOWER(b_users.nickname) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(b_users.email) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(b_titles.title) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(loans.notes) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(b_statuses.description) LIKE ?', [$like]);
            })
            ->select('loans.*');
    }
    public function scopeOrderByAllowed(Builder $query, string $sort, string $order = 'desc'): Builder
    {
        $order = strtolower($order) === 'asc' ? 'asc' : 'desc';
        switch ($sort) {
            case 'borrower_name':
                $query->leftJoin('user_identities AS name_identities', 'loans.user_id', '=', 'name_identities.user_id');
                $query->leftJoin('users AS sort_users', 'loans.user_id', '=', 'sort_users.id');
                $query->leftJoin('user_identities AS sort_identities', 'sort_users.id', '=', 'sort_identities.user_id');
                $query->select('loans.*');
                return $query->orderBy(
                    DB::raw('COALESCE(sort_identities.full_name, sort_users.nickname)'),
                    $order
                );
            case 'book_name':
                $query->leftJoin('editions AS sort_editions', 'loans.edition_id', '=', 'sort_editions.id');
                $query->leftJoin('book_titles AS sort_titles', 'sort_editions.book_title_id', '=', 'sort_titles.id');
                $query->select('loans.*');
                return $query->orderBy('sort_titles.title', $order);
            case 'library':
                $query->leftJoin('libraries', 'loans.library_id', '=', 'libraries.id');
                $query->select('loans.*');
                return $query->orderBy('libraries.name', $order);
            case 'pending':
            case 'not_returned':
            case 'returned':
                $query->leftJoin('statuses AS sort_statuses', 'loans.status_id', '=', 'sort_statuses.id');
                $query->select('loans.*');
                return $query->orderBy('sort_statuses.name', $order);
            case 'loaned_at':
            case 'due_date':
            case 'returned_at':
                return $query->orderBy('loans.' . $sort, $order);
            default:
                return $query->orderBy('loans.created_at', $order);
        }
    }
    public function scopeFilterByStatus(Builder $query, ?string $filterType): Builder
    {
        if (!$filterType) {
            return $query;
        }
        switch (strtolower($filterType)) {
            case 'pending':
                return $query->whereHas('status', fn($q) => $q->where('name', 'pending'));
            case 'overdue':
                return $query->whereHas('status', fn($q) => $q->where('name', 'overdue'));
            case 'not_returned':
                return $query->whereHas('status', fn($q) => $q->where('name', 'approved'))->whereNull('returned_at');
            case 'returned':
                return $query->whereNotNull('returned_at');
            default:
                return $query;
        }
    }
    public function scopeLoanStatus(Builder $q, string $statusName): Builder
    {
        return $q->whereHas('status', function ($query) use ($statusName) {
            $query->where('type', 'loan')
                ->where('name', $statusName);
        });
    }
    public function scopeOverdue(Builder $q): Builder
    {
        return $q->whereNull('loans.returned_at')
            ->where(function ($w) {
                $w->whereHas('status', fn($s) => $s->where('type', 'loan')->where('name', 'overdue'))
                    ->orWhere(function ($qq) {
                        $qq->whereHas('status', fn($s) => $s->where('type', 'loan')->where('name', 'approved'))
                            ->where('loans.due_date', '<', now());
                    });
            });
    }
}

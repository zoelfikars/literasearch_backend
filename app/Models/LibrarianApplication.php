<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LibrarianApplication extends Model
{
    use HasUuids, SoftDeletes;
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'library_id',
        'user_id',
        'status_id',
        'inspector_id',
        'rejection_reason',
        'inspected_at',
    ];
    protected $casts = [
        'inspected_at' => 'datetime',
        'created_at' => 'datetime',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function library()
    {
        return $this->belongsTo(Library::class)->withTrashed();
    }
    public function status()
    {
        return $this->belongsTo(Status::class);
    }
    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }
    public function scopeWithStatusName($q, string $name)
    {
        return $q->whereHas('status', function ($qq) use ($name) {
            $qq->where('type', 'librarian_application')
                ->where('name', $name);
        });
    }
    public function scopePending($q)
    {
        return $q->withStatusName('pending');
    }
    public function scopeApproved($q)
    {
        return $q->withStatusName('approved');
    }
    public function scopeRejected($q)
    {
        return $q->withStatusName('rejected');
    }
    public function scopeStatusName($q, ?string $name)
    {
        if (!$name)
            return $q;
        return $q->withStatusName($name);
    }
    public function scopeForLibrary($q, string $libraryId)
    {
        return $q->where('library_id', $libraryId);
    }
    public function scopeSearchUser($q, ?string $needle)
    {
        if (!$needle)
            return $q;
        $like = '%' . $needle . '%';
        return $q->where(function ($w) use ($like) {
            $w->whereHas('user', function ($uq) use ($like) {
                $uq->where('nickname', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhereHas('identity', fn($iq) => $iq->where('full_name', 'like', $like));
            });
        });
    }
    public function scopeOrderByAllowed($q, string $sort, string $order = 'desc')
    {
        return match ($sort) {
            'name' => $q
                ->leftJoin('users', 'librarian_applications.user_id', '=', 'users.id')
                ->leftJoin('user_identities', 'users.id', '=', 'user_identities.user_id')
                ->orderByRaw('COALESCE(user_identities.full_name, users.nickname) ' . $order)
                ->select('librarian_applications.*'),
            'status' => $q->with('status')->orderBy(
                Status::select('name')->whereColumn('statuses.id', 'librarian_applications.status_id'),
                $order
            ),
            'verified_at' => $q->orderBy('verified_at', $order),
            default => $q->orderBy('created_at', $order),
        };
    }
    public function scopeOwnedBy($q, string $userId)
    {
        return $q->where('librarian_applications.user_id', $userId);
    }
    public function scopeSearch($q, ?string $needle)
    {
        if (!$needle)
            return $q;
        $like = '%' . $needle . '%';
        $q->leftJoin('users as u_search', 'librarian_applications.user_id', '=', 'u_search.id');
        $q->leftJoin('user_identities as ui_search', 'u_search.id', '=', 'ui_search.user_id');
        $q->select('librarian_applications.*');
        return $q->where(function ($q) use ($like) {
            $q->where('u_search.nickname', 'like', $like)
                ->orWhere('u_search.email', 'like', $like)
                ->orWhereHas('status', fn($sq) => $sq->where('name', 'like', $like)->orWhere('description', 'like', $like));
        });
    }
}
